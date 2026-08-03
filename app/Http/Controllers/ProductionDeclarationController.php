<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShiftProductionDeclarationRequest;
use App\Models\DowntimeReason;
use App\Models\Machine;
use App\Models\MachineProductStandard;
use App\Models\Product;
use App\Models\ProductionShift;
use App\Models\ShiftProductionDeclaration;
use App\Models\ShiftProductionDeclarationAudit;
use App\Services\OeeCalculationService;
use App\Services\PlcRuntimeService;
use App\Services\PlcOeeMetricService;
use App\Services\ShiftTimeService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductionDeclarationController extends Controller
{
    public function index(Request $request): View
    {
        $showTrash = $request->boolean('trash');

        if ($showTrash) {
            abort_unless(
                $request->user()->can('production-declarations.restore'),
                403
            );
        }

        $query = ShiftProductionDeclaration::query()
            ->when(
                $showTrash,
                fn ($builder) => $builder->onlyTrashed(),
                fn ($builder) => $builder
            )
            ->with([
                'machine',
                'shift',
                'operator',
                'approver',
                'deleter',
            ])
            ->withCount('items')
            ->withSum(
                'items as converted_total_kg_sum',
                'converted_total_kg'
            )
            ->latest($showTrash ? 'deleted_at' : 'production_date')
            ->latest('id');

        if ($request->filled('machine_id')) {
            $query->where(
                'machine_id',
                $request->integer('machine_id')
            );
        }

        if (!$showTrash && $request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate(
                'production_date',
                '>=',
                $request->date('date_from')
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'production_date',
                '<=',
                $request->date('date_to')
            );
        }

        return view('production-declarations.index', [
            'declarations' => $query
                ->paginate(20)
                ->withQueryString(),
            'machines' => Machine::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get(),
            'showTrash' => $showTrash,
        ]);
    }

    public function create(): View
    {
        return view('production-declarations.create', $this->formData() + [
            'initialItems' => old('items', []),
            'initialDowntimes' => old('downtimes', []),
        ]);
    }

    public function edit(ShiftProductionDeclaration $productionDeclaration): View
    {
        $this->ensureCanModifyDeclaration(
            request(),
            $productionDeclaration
        );
        abort_unless(
            $productionDeclaration->isEditable(),
            409,
            'Chỉ phiếu Nháp hoặc Đã trả lại mới được sửa.'
        );

        $productionDeclaration->load(['items', 'downtimeEntries']);

        $initialItems = old('items', $productionDeclaration->items->map(
            fn ($item): array => [
                'product_id' => $item->product_id,
                'actual_length_m' => $item->actual_length_m,
                'total_quantity' => $item->total_quantity,
                'good_quantity' => $item->good_quantity,
                'reject_quantity' => $item->reject_quantity,
                'scrap_quantity' => $item->scrap_quantity,
                'work_order' => $item->work_order,
                'note' => $item->note,
            ]
        )->values()->all());

        $initialDowntimes = old('downtimes', $productionDeclaration->downtimeEntries->map(
            fn ($entry): array => [
                'downtime_reason_id' => $entry->downtime_reason_id,
                'minutes' => $entry->minutes,
                'note' => $entry->note,
            ]
        )->values()->all());

        return view('production-declarations.edit', $this->formData() + [
            'declaration' => $productionDeclaration,
            'initialItems' => $initialItems,
            'initialDowntimes' => $initialDowntimes,
        ]);
    }


    public function duplicateCheck(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'machine_id' => ['required', 'integer', 'exists:machines,id'],
            'production_shift_id' => ['required', 'integer', 'exists:production_shifts,id'],
            'production_date' => ['required', 'date'],
            'exclude_id' => ['nullable', 'integer', 'exists:shift_production_declarations,id'],
        ]);

        $duplicate = $this->duplicateDeclarationQuery(
            (int) $validated['machine_id'],
            (int) $validated['production_shift_id'],
            $validated['production_date'],
            isset($validated['exclude_id']) ? (int) $validated['exclude_id'] : null
        )
            ->with(['machine:id,code,name', 'shift:id,code,name'])
            ->first();

        if (!$duplicate) {
            return response()->json([
                'duplicate' => false,
                'message' => 'Chưa có phiếu trùng Máy - Ngày - Ca.',
            ]);
        }

        return response()->json([
            'duplicate' => true,
            'message' => sprintf(
                'Đã tồn tại phiếu #%d của máy %s, %s, ngày %s.',
                $duplicate->id,
                $duplicate->machine?->code ?? ('ID ' . $duplicate->machine_id),
                $duplicate->shift?->name ?? ('Ca ID ' . $duplicate->production_shift_id),
                $duplicate->production_date?->format('d/m/Y')
            ),
            'declaration' => [
                'id' => $duplicate->id,
                'status' => $duplicate->status,
                'status_label' => $duplicate->statusLabel(),
                'url' => route('production-declarations.show', $duplicate),
            ],
        ]);
    }

    public function productSearch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'machine_id' => ['required', 'integer', 'exists:machines,id'],
            'production_date' => ['required', 'date'],
            'q' => ['nullable', 'string', 'max:150'],
            'page' => ['nullable', 'integer', 'min:1'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ]);

        $date = $validated['production_date'];
        $term = trim((string) ($validated['q'] ?? ''));
        $page = max((int) ($validated['page'] ?? 1), 1);
        $perPage = 30;

        $standards = MachineProductStandard::query()
            ->with('product')
            ->where('machine_id', $validated['machine_id'])
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $date)
            ->where(function ($query) use ($date): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $date);
            })
            ->whereHas('product', function ($query) use ($term, $validated): void {
                $query->where('is_active', true);

                if (!empty($validated['product_id'])) {
                    $query->whereKey($validated['product_id']);
                    return;
                }

                if ($term !== '') {
                    $query->where(function ($search) use ($term): void {
                        $search->where('material_code', 'like', '%' . $term . '%')
                            ->orWhere('material_name', 'like', '%' . $term . '%');
                    });
                }
            })
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (MachineProductStandard $standard): bool => $standard->product !== null)
            ->unique('product_id')
            ->sortBy(function (MachineProductStandard $standard) use ($term): string {
                $code = mb_strtolower((string) $standard->product->material_code);
                $name = mb_strtolower((string) $standard->product->material_name);
                $needle = mb_strtolower($term);

                $priority = $needle !== '' && str_starts_with($code, $needle)
                    ? '0'
                    : ($needle !== '' && str_contains($code, $needle) ? '1' : '2');

                return $priority . '|' . $code . '|' . $name;
            })
            ->values();

        $total = $standards->count();
        $pageItems = $standards
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        $results = $pageItems->map(function (MachineProductStandard $standard): array {
            $product = $standard->product;

            return [
                'id' => $product->id,
                'text' => $product->material_code . ' - ' . $product->material_name,
                'code' => $product->material_code,
                'name' => $product->material_name,
                'unit' => $product->unit,
                'standard_weight_g' => (float) $product->standard_weight_g,
                'default_length_m' => (float) $product->default_length_m,
                'standard_id' => $standard->id,
                'standard_rate' => (float) $standard->standard_rate,
                'rate_unit' => $standard->rate_unit,
            ];
        });

        return response()->json([
            'results' => $results,
            'pagination' => [
                'more' => ($page * $perPage) < $total,
            ],
            'meta' => [
                'machine_id' => (int) $validated['machine_id'],
                'production_date' => $date,
                'term' => $term,
                'total_matching_products' => $total,
            ],
        ]);
    }

    public function productOptions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'machine_id' => ['required', 'integer', 'exists:machines,id'],
            'production_date' => ['required', 'date'],
        ]);

        $date = $validated['production_date'];

        $rows = MachineProductStandard::query()
            ->with('product')
            ->where('machine_id', $validated['machine_id'])
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $date)
            ->where(function ($query) use ($date): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $date);
            })
            ->whereHas('product', fn ($query) => $query->where('is_active', true))
            ->orderByDesc('effective_from')
            ->get()
            ->unique('product_id')
            ->values()
            ->map(fn (MachineProductStandard $standard): array => [
                'product_id' => $standard->product_id,
                'standard_id' => $standard->id,
                'code' => $standard->product->material_code,
                'name' => $standard->product->material_name,
                'unit' => $standard->product->unit,
                'standard_weight_g' => (float) $standard->product->standard_weight_g,
                'default_length_m' => (float) $standard->product->default_length_m,
                'standard_rate' => (float) $standard->standard_rate,
                'rate_unit' => $standard->rate_unit,
            ]);

        return response()->json(['data' => $rows]);
    }

    public function store(
        StoreShiftProductionDeclarationRequest $request,
        ShiftTimeService $shiftTimeService,
        PlcOeeMetricService $plcMetricService,
        OeeCalculationService $oeeService
    ): RedirectResponse {
        $data = $request->validated();

        $this->ensureDeclarationSlotIsAvailable(
            (int) $data['machine_id'],
            (int) $data['production_shift_id'],
            $data['production_date']
        );

        try {
            $declaration = DB::transaction(function () use (
                $request,
                $data,
                $shiftTimeService
            ) {
                $declaration = new ShiftProductionDeclaration();

                $this->persistDeclaration(
                    $declaration,
                    $data,
                    $request,
                    $shiftTimeService
                );

                $this->audit(
                    $declaration,
                    $request,
                    'created',
                    null,
                    $declaration->status,
                    null,
                    null
                );

                return $declaration;
            });
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages([
                'production_shift_id' => $this->duplicateValidationMessage(
                    (int) $data['machine_id'],
                    (int) $data['production_shift_id'],
                    $data['production_date']
                ),
            ]);
        }

        $oeeResult = $this->calculateOeeAutomatically(
            $declaration,
            $plcMetricService,
            $oeeService
        );

        return redirect()
            ->route('production-declarations.show', $oeeResult)
            ->with(
                'success',
                $oeeResult->status === ShiftProductionDeclaration::STATUS_DRAFT
                    ? (
                        $oeeResult->oee !== null
                            ? 'Đã lưu nháp và tự động tính OEE theo MD488/MD264.'
                            : 'Đã lưu nháp. Hệ thống đã kiểm tra OEE theo MD488/MD264 nhưng chưa đủ dữ liệu để tính hoàn chỉnh.'
                    )
                    : (
                        $oeeResult->oee !== null
                            ? 'Đã gửi phiếu và tự động tính OEE theo MD488/MD264.'
                            : 'Đã gửi phiếu. Hệ thống đã kiểm tra OEE theo MD488/MD264 nhưng chưa đủ dữ liệu để tính hoàn chỉnh.'
                    )
            );
    }

    public function update(
        StoreShiftProductionDeclarationRequest $request,
        ShiftProductionDeclaration $productionDeclaration,
        ShiftTimeService $shiftTimeService,
        PlcOeeMetricService $plcMetricService,
        OeeCalculationService $oeeService
    ): RedirectResponse {
        $this->ensureCanModifyDeclaration(
            $request,
            $productionDeclaration
        );

        abort_unless(
            $productionDeclaration->isEditable(),
            409,
            'Phiếu không còn ở trạng thái cho phép sửa.'
        );

        $data = $request->validated();

        $this->ensureDeclarationSlotIsAvailable(
            (int) $data['machine_id'],
            (int) $data['production_shift_id'],
            $data['production_date'],
            $productionDeclaration->id
        );

        $before = $this->snapshot($productionDeclaration);
        $fromStatus = $productionDeclaration->status;

        try {
            DB::transaction(function () use (
                $request,
                $data,
                $productionDeclaration,
                $shiftTimeService,
                $before,
                $fromStatus
            ): void {
                $this->persistDeclaration(
                    $productionDeclaration,
                    $data,
                    $request,
                    $shiftTimeService
                );

                $this->audit(
                    $productionDeclaration,
                    $request,
                    'updated',
                    $fromStatus,
                    $productionDeclaration->status,
                    null,
                    $before
                );
            });
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages([
                'production_shift_id' => $this->duplicateValidationMessage(
                    (int) $data['machine_id'],
                    (int) $data['production_shift_id'],
                    $data['production_date'],
                    $productionDeclaration->id
                ),
            ]);
        }

        $oeeResult = $this->calculateOeeAutomatically(
            $productionDeclaration,
            $plcMetricService,
            $oeeService
        );

        return redirect()
            ->route('production-declarations.show', $oeeResult)
            ->with(
                'success',
                $oeeResult->status === ShiftProductionDeclaration::STATUS_DRAFT
                    ? (
                        $oeeResult->oee !== null
                            ? 'Đã cập nhật, lưu nháp và tự động tính lại OEE theo MD488/MD264.'
                            : 'Đã cập nhật và lưu nháp. Hệ thống đã kiểm tra OEE theo MD488/MD264 nhưng chưa đủ dữ liệu.'
                    )
                    : (
                        $oeeResult->oee !== null
                            ? 'Đã cập nhật, gửi lại và tự động tính OEE theo MD488/MD264.'
                            : 'Đã cập nhật và gửi lại. Hệ thống đã kiểm tra OEE theo MD488/MD264 nhưng chưa đủ dữ liệu.'
                    )
            );
    }

    public function destroy(
        Request $request,
        ShiftProductionDeclaration $productionDeclaration
    ): RedirectResponse {
        abort_unless(
            $request->user()->can('production-declarations.delete'),
            403
        );

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'reason.required' => 'Vui lòng nhập lý do xóa phiếu.',
            'reason.min' => 'Lý do xóa phải có ít nhất 5 ký tự.',
        ]);

        $before = $this->snapshot($productionDeclaration);

        DB::transaction(function () use (
            $request,
            $productionDeclaration,
            $validated,
            $before
        ): void {
            $this->audit(
                $productionDeclaration,
                $request,
                'deleted',
                $productionDeclaration->status,
                $productionDeclaration->status,
                $validated['reason'],
                $before
            );

            // record_scope = ID giúp giải phóng bộ Máy - Ca - Ngày
            // để có thể tạo phiếu mới sau khi xóa mềm.
            $productionDeclaration->update([
                'record_scope' => $productionDeclaration->id,
                'deleted_by' => $request->user()->id,
                'delete_reason' => $validated['reason'],
            ]);

            $productionDeclaration->delete();
        });

        return redirect()
            ->route('production-declarations.index')
            ->with(
                'success',
                'Đã chuyển phiếu #' . $productionDeclaration->id
                . ' vào thùng rác.'
            );
    }

    public function restore(
        Request $request,
        int $productionDeclaration
    ): RedirectResponse {
        abort_unless(
            $request->user()->can('production-declarations.restore'),
            403
        );

        $declaration = ShiftProductionDeclaration::query()
            ->onlyTrashed()
            ->with(['items', 'downtimeEntries'])
            ->findOrFail($productionDeclaration);

        $duplicateExists = ShiftProductionDeclaration::query()
            ->where('machine_id', $declaration->machine_id)
            ->where(
                'production_shift_id',
                $declaration->production_shift_id
            )
            ->whereDate(
                'production_date',
                $declaration->production_date
            )
            ->exists();

        if ($duplicateExists) {
            return back()->with(
                'error',
                'Không thể khôi phục phiếu #' . $declaration->id
                . ' vì đã có một phiếu đang hoạt động cùng Máy - Ngày - Ca.'
            );
        }

        DB::transaction(function () use (
            $request,
            $declaration
        ): void {
            $before = $this->snapshot($declaration);

            // Khôi phục trước với record_scope riêng, sau đó chuyển về 0.
            $declaration->restore();

            $declaration->update([
                'record_scope' => 0,
                'deleted_by' => null,
                'delete_reason' => null,
            ]);

            $this->audit(
                $declaration,
                $request,
                'restored',
                $declaration->status,
                $declaration->status,
                'Khôi phục phiếu từ thùng rác.',
                $before
            );
        });

        return redirect()
            ->route(
                'production-declarations.show',
                $declaration
            )
            ->with(
                'success',
                'Đã khôi phục phiếu #' . $declaration->id . '.'
            );
    }

    public function show(ShiftProductionDeclaration $productionDeclaration): View
    {
        $productionDeclaration->load([
            'machine',
            'shift',
            'operator',
            'approver',
            'returner',
            'locker',
            'unlocker',
            'items.product',
            'downtimeEntries.reason',
            'audits.user',
        ]);

        return view('production-declarations.show', ['declaration' => $productionDeclaration]);
    }

    public function submit(
        Request $request,
        ShiftProductionDeclaration $productionDeclaration,
        PlcOeeMetricService $plcMetricService,
        OeeCalculationService $oeeService
    ): RedirectResponse {
        $this->ensureCanModifyDeclaration(
            $request,
            $productionDeclaration
        );
        abort_unless($productionDeclaration->isEditable(), 409);

        $before = $this->snapshot($productionDeclaration);
        $fromStatus = $productionDeclaration->status;

        $productionDeclaration->update([
            'status' => ShiftProductionDeclaration::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'returned_by' => null,
            'returned_at' => null,
            'return_reason' => null,
        ]);

        $this->audit(
            $productionDeclaration,
            $request,
            'submitted',
            $fromStatus,
            $productionDeclaration->status,
            null,
            $before
        );

        $oeeResult = $this->calculateOeeAutomatically(
            $productionDeclaration,
            $plcMetricService,
            $oeeService
        );

        return back()->with(
            $oeeResult->oee !== null ? 'success' : 'warning',
            $oeeResult->oee !== null
                ? 'Đã gửi phiếu và tự động tính OEE theo MD488/MD264.'
                : 'Đã gửi phiếu nhưng chưa đủ dữ liệu MD488/MD264 để tính OEE hoàn chỉnh. Chi tiết đã được lưu trên phiếu.'
        );
    }

    public function returnForCorrection(
        Request $request,
        ShiftProductionDeclaration $productionDeclaration
    ): RedirectResponse {
        abort_unless($request->user()->can('production-declarations.approve'), 403);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'reason.required' => 'Vui lòng nhập lý do trả lại.',
            'reason.min' => 'Lý do trả lại phải có ít nhất 5 ký tự.',
        ]);

        abort_unless(in_array($productionDeclaration->status, [
            ShiftProductionDeclaration::STATUS_SUBMITTED,
            ShiftProductionDeclaration::STATUS_APPROVED,
        ], true), 409);

        $before = $this->snapshot($productionDeclaration);
        $fromStatus = $productionDeclaration->status;

        $productionDeclaration->update([
            'status' => ShiftProductionDeclaration::STATUS_RETURNED,
            'returned_by' => $request->user()->id,
            'returned_at' => now(),
            'return_reason' => $data['reason'],
            'approved_by' => null,
            'approved_at' => null,
        ]);

        $this->audit(
            $productionDeclaration,
            $request,
            'returned',
            $fromStatus,
            ShiftProductionDeclaration::STATUS_RETURNED,
            $data['reason'],
            $before
        );

        return back()->with('warning', 'Đã trả lại phiếu để người nhập chỉnh sửa.');
    }

    public function recalculate(
        Request $request,
        ShiftProductionDeclaration $productionDeclaration,
        OeeCalculationService $oeeService,
        PlcOeeMetricService $plcMetricService
    ): RedirectResponse {
        abort_unless($request->user()->can('oee.recalculate'), 403);

        $metrics = $plcMetricService->collect($productionDeclaration);
        $result = $oeeService->calculate($productionDeclaration->fresh(), $metrics);

        if ($result->oee === null) {
            return back()->with('warning', $result->calculation_note);
        }

        return back()->with(
            'success',
            'Đã đọc MD488/MD264 và tính lại A, P, Q, OEE thành công. '
            . $result->calculation_note
        );
    }

    public function approve(
        Request $request,
        ShiftProductionDeclaration $productionDeclaration,
        OeeCalculationService $oeeService,
        PlcOeeMetricService $plcMetricService
    ): RedirectResponse {
        abort_unless($request->user()->can('production-declarations.approve'), 403);

        if ($productionDeclaration->status !== ShiftProductionDeclaration::STATUS_SUBMITTED) {
            return back()->with('error', 'Chỉ phiếu Chờ phê duyệt mới được phê duyệt.');
        }

        $before = $this->snapshot($productionDeclaration);
        $metrics = $plcMetricService->collect($productionDeclaration);
        $result = $oeeService->calculate($productionDeclaration->fresh(), $metrics);

        if ($result->oee === null) {
            return back()->with(
                'error',
                'Không thể phê duyệt vì chưa tính được OEE. ' . $result->calculation_note
            );
        }

        $result->update([
            'status' => ShiftProductionDeclaration::STATUS_APPROVED,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $this->audit(
            $result,
            $request,
            'approved',
            ShiftProductionDeclaration::STATUS_SUBMITTED,
            ShiftProductionDeclaration::STATUS_APPROVED,
            null,
            $before
        );

        return back()->with('success', 'Đã phê duyệt phiếu. Phiếu chưa khóa và vẫn có thể được trả lại.');
    }

    public function lock(
        Request $request,
        ShiftProductionDeclaration $productionDeclaration
    ): RedirectResponse {
        abort_unless($request->user()->can('production-declarations.lock'), 403);

        if ($productionDeclaration->status !== ShiftProductionDeclaration::STATUS_APPROVED) {
            return back()->with('error', 'Chỉ phiếu đã phê duyệt mới được khóa.');
        }

        $before = $this->snapshot($productionDeclaration);

        $productionDeclaration->update([
            'status' => ShiftProductionDeclaration::STATUS_LOCKED,
            'locked_by' => $request->user()->id,
            'locked_at' => now(),
        ]);

        $this->audit(
            $productionDeclaration,
            $request,
            'locked',
            ShiftProductionDeclaration::STATUS_APPROVED,
            ShiftProductionDeclaration::STATUS_LOCKED,
            null,
            $before
        );

        return back()->with('success', 'Đã khóa phiếu. Dữ liệu không thể sửa hoặc trả lại.');
    }

    public function unlock(
        Request $request,
        ShiftProductionDeclaration $productionDeclaration
    ): RedirectResponse {
        abort_unless($request->user()->can('production-declarations.unlock'), 403);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'reason.required' => 'Vui lòng nhập lý do mở khóa.',
            'reason.min' => 'Lý do mở khóa phải có ít nhất 5 ký tự.',
        ]);

        if ($productionDeclaration->status !== ShiftProductionDeclaration::STATUS_LOCKED) {
            return back()->with('error', 'Phiếu chưa ở trạng thái khóa.');
        }

        $before = $this->snapshot($productionDeclaration);

        $productionDeclaration->update([
            'status' => ShiftProductionDeclaration::STATUS_APPROVED,
            'unlocked_by' => $request->user()->id,
            'unlocked_at' => now(),
            'unlock_reason' => $data['reason'],
        ]);

        $this->audit(
            $productionDeclaration,
            $request,
            'unlocked',
            ShiftProductionDeclaration::STATUS_LOCKED,
            ShiftProductionDeclaration::STATUS_APPROVED,
            $data['reason'],
            $before
        );

        return back()->with('warning', 'Đã mở khóa phiếu và chuyển về trạng thái Đã phê duyệt.');
    }

    private function duplicateDeclarationQuery(
        int $machineId,
        int $shiftId,
        string $productionDate,
        ?int $excludeId = null
    ) {
        return ShiftProductionDeclaration::query()
            ->where('machine_id', $machineId)
            ->where('production_shift_id', $shiftId)
            ->whereDate('production_date', $productionDate)
            ->when(
                $excludeId,
                fn ($query) => $query->whereKeyNot($excludeId)
            );
    }

    private function ensureDeclarationSlotIsAvailable(
        int $machineId,
        int $shiftId,
        string $productionDate,
        ?int $excludeId = null
    ): void {
        if (!$this->duplicateDeclarationQuery(
            $machineId,
            $shiftId,
            $productionDate,
            $excludeId
        )->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            'production_shift_id' => $this->duplicateValidationMessage(
                $machineId,
                $shiftId,
                $productionDate,
                $excludeId
            ),
        ]);
    }

    private function duplicateValidationMessage(
        int $machineId,
        int $shiftId,
        string $productionDate,
        ?int $excludeId = null
    ): string {
        $duplicate = $this->duplicateDeclarationQuery(
            $machineId,
            $shiftId,
            $productionDate,
            $excludeId
        )
            ->with(['machine:id,code,name', 'shift:id,code,name'])
            ->first();

        if (!$duplicate) {
            return 'Đã tồn tại một phiếu khác có cùng Máy - Ngày - Ca. Vui lòng tải lại trang và kiểm tra.';
        }

        return sprintf(
            'Đã tồn tại phiếu #%d của máy %s, %s, ngày %s. Vui lòng mở phiếu đã có hoặc chọn Máy - Ngày - Ca khác.',
            $duplicate->id,
            $duplicate->machine?->code ?? ('ID ' . $machineId),
            $duplicate->shift?->name ?? ('Ca ID ' . $shiftId),
            $duplicate->production_date?->format('d/m/Y')
        );
    }

    private function formData(): array
    {
        return [
            'machines' => Machine::query()->where('is_active', true)->orderBy('code')->get(),
            'shifts' => ProductionShift::query()->where('is_active', true)->orderBy('display_order')->get(),
            'reasons' => DowntimeReason::query()->where('is_active', true)->orderBy('display_order')->get(),
            'products' => Product::query()->where('is_active', true)->orderBy('material_code')->get(),
        ];
    }

    private function persistDeclaration(
        ShiftProductionDeclaration $declaration,
        array $data,
        Request $request,
        ShiftTimeService $shiftTimeService
    ): void {
        $machine = Machine::query()->findOrFail($data['machine_id']);
        $shift = ProductionShift::query()->findOrFail($data['production_shift_id']);
        [$startedAt, $endedAt] = $shiftTimeService->range($shift, $data['production_date']);

        $plannedDowntime = (float) ($data['planned_downtime_minutes'] ?? 0);
        $shiftMinutes = $startedAt->diffInMinutes($endedAt);
        $plannedProduction = max($shiftMinutes - $plannedDowntime, 0);

        $totals = collect($data['items'])->reduce(function (array $carry, array $item): array {
            $carry['total'] += (float) ($item['total_quantity'] ?? 0);
            $carry['good'] += (float) ($item['good_quantity'] ?? 0);
            $carry['reject'] += (float) ($item['reject_quantity'] ?? 0);
            return $carry;
        }, ['total' => 0.0, 'good' => 0.0, 'reject' => 0.0]);

        $quality = $totals['total'] > 0
            ? min($totals['good'] / $totals['total'], 1)
            : null;

        $status = $data['submit_action'] === 'submit'
            ? ShiftProductionDeclaration::STATUS_SUBMITTED
            : ShiftProductionDeclaration::STATUS_DRAFT;

        $declaration->fill([
            'machine_id' => $machine->id,
            'production_shift_id' => $shift->id,
            'production_date' => $data['production_date'],
            'shift_started_at' => $startedAt,
            'shift_ended_at' => $endedAt,
            'product_code' => null,
            'product_name' => null,
            'work_order' => null,
            'good_quantity' => $totals['good'],
            'reject_quantity' => $totals['reject'],
            'total_quantity' => $totals['total'],
            'planned_downtime_minutes' => $plannedDowntime,
            'planned_production_minutes' => $plannedProduction,
            'plc_runtime_minutes' => null,
            'unplanned_downtime_minutes' => null,
            'ideal_rate_per_minute' => null,
            'availability' => null,
            'performance' => null,
            'quality_rate' => $quality,
            'oee' => null,
            'status' => $status,
            'operator_id' => $declaration->exists
                ? $declaration->operator_id
                : $request->user()->id,
            'submitted_at' => $status === ShiftProductionDeclaration::STATUS_SUBMITTED
                ? now()
                : null,
            'returned_by' => null,
            'returned_at' => null,
            'return_reason' => null,
            'note' => $data['note'] ?? null,
            'calculation_note' => 'Phiếu vừa được lưu. Hệ thống sẽ tự động tính A theo MD488, P theo MD264 ổn định và Q theo sản lượng sau khi hoàn tất lưu chi tiết sản phẩm.',
        ])->save();

        $declaration->items()->delete();
        $declaration->downtimeEntries()->delete();

        foreach ($data['items'] as $index => $item) {
            $product = Product::query()->findOrFail($item['product_id']);
            $standard = MachineProductStandard::query()
                ->where('machine_id', $machine->id)
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->whereDate('effective_from', '<=', $data['production_date'])
                ->where(function ($query) use ($data): void {
                    $query->whereNull('effective_to')
                        ->orWhereDate('effective_to', '>=', $data['production_date']);
                })
                ->latest('effective_from')
                ->firstOrFail();

            $actualLength = (float) ($item['actual_length_m'] ?? $product->default_length_m ?? 0);
            $total = (float) $item['total_quantity'];
            $good = (float) $item['good_quantity'];
            $reject = (float) ($item['reject_quantity'] ?? 0);
            $scrap = (float) ($item['scrap_quantity'] ?? 0);

            $totalKg = $this->quantityToKg(
                $total,
                $product->unit,
                $product->standard_weight_g,
                $actualLength
            );
            $goodKg = $this->quantityToKg(
                $good,
                $product->unit,
                $product->standard_weight_g,
                $actualLength
            );
            $rejectKg = $this->quantityToKg(
                $reject,
                $product->unit,
                $product->standard_weight_g,
                $actualLength
            );

            $declaration->items()->create([
                'product_id' => $product->id,
                'machine_product_standard_id' => $standard->id,
                'product_code_snapshot' => $product->material_code,
                'product_name_snapshot' => $product->material_name,
                'unit_snapshot' => $product->unit,
                'standard_weight_g_snapshot' => $product->standard_weight_g,
                'default_length_m_snapshot' => $product->default_length_m,
                'actual_length_m' => $actualLength ?: null,
                'standard_rate_snapshot' => $standard->standard_rate,
                'rate_unit_snapshot' => $standard->rate_unit,
                'total_quantity' => $total,
                'good_quantity' => $good,
                'reject_quantity' => $reject,
                'scrap_quantity' => $scrap,
                'converted_total_kg' => $totalKg !== null ? round($totalKg, 3) : 0,
                'converted_good_kg' => $goodKg !== null ? round($goodKg, 3) : 0,
                'converted_reject_kg' => $rejectKg !== null ? round($rejectKg, 3) : 0,
                'converted_scrap_kg' => round($scrap, 3),
                'work_order' => $item['work_order'] ?? null,
                'note' => $item['note'] ?? null,
                'display_order' => $index,
            ]);
        }

        foreach ($data['downtimes'] ?? [] as $entry) {
            if (empty($entry['downtime_reason_id']) || (float) ($entry['minutes'] ?? 0) <= 0) {
                continue;
            }

            $declaration->downtimeEntries()->create([
                'downtime_reason_id' => $entry['downtime_reason_id'],
                'minutes' => $entry['minutes'],
                'note' => $entry['note'] ?? null,
            ]);
        }
    }

    private function audit(
        ShiftProductionDeclaration $declaration,
        Request $request,
        string $action,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $reason = null,
        ?array $before = null
    ): void {
        ShiftProductionDeclarationAudit::query()->create([
            'shift_production_declaration_id' => $declaration->id,
            'user_id' => $request->user()?->id,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $reason,
            'before_data' => $before,
            'after_data' => $this->snapshot($declaration),
            'created_at' => now(),
        ]);
    }

    private function snapshot(ShiftProductionDeclaration $declaration): array
    {
        $declaration->loadMissing(['items', 'downtimeEntries']);

        return [
            'header' => $declaration->only([
                'machine_id',
                'production_shift_id',
                'production_date',
                'planned_downtime_minutes',
                'good_quantity',
                'reject_quantity',
                'total_quantity',
                'status',
                'note',
                'record_scope',
                'deleted_by',
                'delete_reason',
                'deleted_at',
            ]),
            'items' => $declaration->items->map->toArray()->values()->all(),
            'downtimes' => $declaration->downtimeEntries->map->toArray()->values()->all(),
        ];
    }

    private function ensureCanModifyDeclaration(
        Request $request,
        ShiftProductionDeclaration $declaration
    ): void {
        $user = $request->user();

        abort_unless(
            $user?->can('production-declarations.update'),
            403
        );

        /*
         * Nhân viên vận hành chỉ được sửa/gửi phiếu do chính mình nhập.
         * Tổ trưởng, Quản đốc, Admin và Super Admin được xử lý theo quyền role.
         */
        if ($user->hasRole('VAN_HANH')) {
            abort_unless(
                (int) $declaration->operator_id === (int) $user->id,
                403,
                'Bạn chỉ được sửa hoặc gửi phiếu do chính mình nhập.'
            );
        }
    }

    private function calculateOeeAutomatically(
        ShiftProductionDeclaration $declaration,
        PlcOeeMetricService $plcMetricService,
        OeeCalculationService $oeeService
    ): ShiftProductionDeclaration {
        try {
            $declaration = $declaration->fresh([
                'machine.plcs',
                'shift',
                'items',
                'downtimeEntries.reason',
            ]);

            $metrics = $plcMetricService->collect($declaration);

            return $oeeService->calculate(
                $declaration,
                $metrics
            );
        } catch (\Throwable $exception) {
            Log::error('Tự động tính OEE sau khi lưu phiếu thất bại.', [
                'declaration_id' => $declaration->id,
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            $declaration->update([
                'availability' => null,
                'performance' => null,
                'oee' => null,
                'calculation_note' => 'Phiếu đã được lưu nhưng tự động tính OEE gặp lỗi kỹ thuật: '
                    . $exception->getMessage()
                    . ' Vui lòng kiểm tra cấu hình OEE hoặc dùng nút Tính lại OEE.',
            ]);

            return $declaration->fresh();
        }
    }

    private function refreshRuntimeFromHistory(
        ShiftProductionDeclaration $declaration,
        PlcRuntimeService $runtimeService
    ): array {
        $declaration->loadMissing('machine');

        if (!$declaration->shift_started_at || !$declaration->shift_ended_at) {
            $runtime = [
                'minutes' => null,
                'ok' => false,
                'note' => 'Phiếu không có đầy đủ thời điểm bắt đầu/kết thúc ca.',
            ];
        } else {
            $runtime = $runtimeService->calculateMinutes(
                $declaration->machine,
                $declaration->shift_started_at->copy(),
                $declaration->shift_ended_at->copy()
            );
        }

        $plannedMinutes = max((float) $declaration->planned_production_minutes, 0);

        $declaration->update([
            'plc_runtime_minutes' => $runtime['minutes'],
            'unplanned_downtime_minutes' => $runtime['minutes'] !== null
                ? max($plannedMinutes - (float) $runtime['minutes'], 0)
                : null,
        ]);

        return $runtime;
    }

    private function quantityToKg(
        float $quantity,
        ?string $unit,
        mixed $standardWeightG,
        ?float $actualLengthM = null
    ): ?float {
        $normalizedUnit = mb_strtolower(trim((string) $unit));
        $normalizedUnit = str_replace([' ', '.', '́'], '', $normalizedUnit);
        $weightG = (float) $standardWeightG;

        if (in_array($normalizedUnit, ['kg', 'kilogram', 'kilôgam'], true)) {
            return $quantity;
        }

        if (in_array($normalizedUnit, ['t', 'tan', 'tấn', 'ton', 'tonne'], true)) {
            return $quantity * 1000;
        }

        if (in_array($normalizedUnit, ['m', 'met', 'mét', 'meter', 'metre'], true)) {
            $lengthM = (float) $actualLengthM;

            if ($weightG <= 0 || $lengthM <= 0) {
                return null;
            }

            // Với sản phẩm có ĐVT danh mục là mét:
            // Công nhân vẫn nhập số cây/cuộn thực tế.
            // Khối lượng = Số cây/cuộn × Chiều dài thực tế × g/m ÷ 1.000.
            return $quantity * $lengthM * $weightG / 1000;
        }

        // Với cây, cuộn, cái... định mức là g/ĐVT.
        return $weightG > 0 ? ($quantity * $weightG / 1000) : null;
    }
}
