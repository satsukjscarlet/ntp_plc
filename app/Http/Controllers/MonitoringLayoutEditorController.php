<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\MonitoringLayoutColumn;
use App\Models\MonitoringLayoutRow;
use App\Models\MonitoringLayoutSection;
use App\Models\PlcTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MonitoringLayoutEditorController extends Controller
{
    public function index(Request $request, string $machineCode): View
    {
        $machine = Machine::query()
            ->where('code', $machineCode)
            ->firstOrFail();

        $sections = MonitoringLayoutSection::query()
            ->with([
                'rows' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('display_order')
                    ->orderBy('name_vi'),
                'columns' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('display_order')
                    ->orderBy('name_vi'),
            ])
            ->where('machine_id', $machine->id)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name_vi')
            ->get();

        $plcIds = $machine->plcs()->pluck('id');

        $tags = PlcTag::query()
            ->whereIn('plc_id', $plcIds)
            ->orderByRaw("COALESCE(NULLIF(display_name, ''), source_name)")
            ->get();

        $layoutData = $sections->map(function ($section) use ($tags): array {
            return [
                'id' => $section->id,
                'code' => $section->code,
                'name' => $section->name_vi,
                'rows' => $section->rows->map(fn ($row): array => [
                    'id' => $row->id,
                    'code' => $row->code,
                    'name' => $row->name_vi,
                ])->values()->all(),
                'columns' => $section->columns->map(fn ($column): array => [
                    'id' => $column->id,
                    'code' => $column->code,
                    'name' => $column->name_vi,
                ])->values()->all(),
                'tags' => $tags
                    ->where('monitoring_section_id', $section->id)
                    ->values()
                    ->map(fn (PlcTag $tag): array => $this->tagPayload($tag))
                    ->all(),
            ];
        })->values()->all();

        $allTags = $tags
            ->map(fn (PlcTag $tag): array => $this->tagPayload($tag))
            ->values()
            ->all();

        return view('machines.monitoring-layout-dragdrop.index', [
            'machine' => $machine,
            'sections' => $sections,
            'layoutDataJson' => json_encode(
                $layoutData,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
            'allTagsJson' => json_encode(
                $allTags,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
        ]);
    }

    /**
     * Một endpoint duy nhất cho cả:
     * - cập nhật vị trí kéo thả;
     * - cập nhật tên, đơn vị, ngưỡng, màu, alarm và trend.
     */
    public function updateTag(
        Request $request,
        string $machineCode,
        PlcTag $tag
    ): JsonResponse {
        $machine = Machine::query()
            ->where('code', $machineCode)
            ->firstOrFail();

        abort_unless(
            $machine->plcs()->whereKey($tag->plc_id)->exists(),
            404
        );

        if ($this->isSettingsRequest($request)) {
            return $this->updateDisplaySettings($request, $tag);
        }

        return $this->updatePosition($request, $machine, $tag);
    }

    private function updatePosition(
        Request $request,
        Machine $machine,
        PlcTag $tag
    ): JsonResponse {
        $data = $request->validate([
            'monitoring_section_id' => ['nullable', 'integer'],
            'monitoring_row_id' => ['nullable', 'integer'],
            'monitoring_column_id' => ['nullable', 'integer'],
            'replace_existing' => ['nullable', 'boolean'],
        ], [
            'monitoring_section_id.integer' => 'Loại thông số không hợp lệ.',
            'monitoring_row_id.integer' => 'Dòng hiển thị không hợp lệ.',
            'monitoring_column_id.integer' => 'Cột hiển thị không hợp lệ.',
        ]);

        $sectionId = $data['monitoring_section_id'] ?? null;
        $rowId = $data['monitoring_row_id'] ?? null;
        $columnId = $data['monitoring_column_id'] ?? null;
        $replaceExisting = (bool) ($data['replace_existing'] ?? false);

        if (!$sectionId) {
            $tag->update([
                'monitoring_section_id' => null,
                'monitoring_row_id' => null,
                'monitoring_column_id' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã đưa tag về danh sách chưa bố trí.',
                'tag' => $this->tagPayload($tag->fresh()),
            ]);
        }

        $section = MonitoringLayoutSection::query()
            ->where('machine_id', $machine->id)
            ->where('is_active', true)
            ->findOrFail((int) $sectionId);

        $row = $rowId
            ? MonitoringLayoutRow::query()
                ->where('section_id', $section->id)
                ->where('is_active', true)
                ->findOrFail((int) $rowId)
            : null;

        $column = $columnId
            ? MonitoringLayoutColumn::query()
                ->where('section_id', $section->id)
                ->where('is_active', true)
                ->findOrFail((int) $columnId)
            : null;

        if (!$row || !$column) {
            return response()->json([
                'success' => false,
                'message' => 'Vị trí thả tag chưa có đủ dòng và cột.',
            ], 422);
        }

        $duplicate = PlcTag::query()
            ->whereIn('plc_id', $machine->plcs()->pluck('id'))
            ->where('monitoring_section_id', $section->id)
            ->where('monitoring_row_id', $row->id)
            ->where('monitoring_column_id', $column->id)
            ->whereKeyNot($tag->id)
            ->first();

        if ($duplicate && !$replaceExisting) {
            return response()->json([
                'success' => false,
                'requires_confirmation' => true,
                'message' => 'Ô này đang có tag: '
                    . ($duplicate->display_name ?: $duplicate->source_name)
                    . '. Bạn có muốn thay thế không?',
                'existing_tag_id' => $duplicate->id,
            ], 409);
        }

        DB::transaction(function () use (
            $tag,
            $duplicate,
            $replaceExisting,
            $section,
            $row,
            $column
        ): void {
            if ($duplicate && $replaceExisting) {
                $duplicate->update([
                    'monitoring_section_id' => null,
                    'monitoring_row_id' => null,
                    'monitoring_column_id' => null,
                ]);
            }

            $tag->update([
                'monitoring_section_id' => $section->id,
                'monitoring_row_id' => $row->id,
                'monitoring_column_id' => $column->id,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Đã bố trí tag vào vị trí mới.',
            'tag' => $this->tagPayload($tag->fresh()),
            'replaced_tag' => $duplicate && $replaceExisting
                ? $this->tagPayload($duplicate->fresh())
                : null,
        ]);
    }

    private function updateDisplaySettings(
        Request $request,
        PlcTag $tag
    ): JsonResponse {
        $data = $request->validate([
            'display_name' => ['nullable', 'string', 'max:255'],
            'display_unit' => ['nullable', 'string', 'max:30'],
            'display_decimals' => ['required', 'integer', 'min:0', 'max:6'],
            'warning_low' => ['nullable', 'numeric'],
            'warning_high' => ['nullable', 'numeric'],
            'alarm_enabled' => ['required', 'boolean'],
            'blink_on_alarm' => ['required', 'boolean'],
            'chart_enabled' => ['required', 'boolean'],
            'display_color' => [
                'nullable',
                'string',
                'max:30',
                'regex:/^(#[0-9a-fA-F]{6}|[a-zA-Z]+)$/',
            ],
        ], [
            'display_name.max' => 'Tên hiển thị tối đa 255 ký tự.',
            'display_unit.max' => 'Đơn vị tối đa 30 ký tự.',
            'display_decimals.required' => 'Vui lòng nhập số chữ số thập phân.',
            'display_decimals.min' => 'Số chữ số thập phân không được âm.',
            'display_decimals.max' => 'Chỉ hỗ trợ tối đa 6 chữ số thập phân.',
            'warning_low.numeric' => 'Ngưỡng thấp phải là số.',
            'warning_high.numeric' => 'Ngưỡng cao phải là số.',
            'display_color.regex' =>
                'Màu phải là mã HEX như #198754 hoặc tên màu.',
        ]);

        if (
            ($data['warning_low'] ?? null) !== null
            && ($data['warning_high'] ?? null) !== null
            && (float) $data['warning_low'] >= (float) $data['warning_high']
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Ngưỡng thấp phải nhỏ hơn ngưỡng cao.',
                'errors' => [
                    'warning_low' => [
                        'Ngưỡng thấp phải nhỏ hơn ngưỡng cao.',
                    ],
                ],
            ], 422);
        }

        $tag->update([
            'display_name' => filled($data['display_name'] ?? null)
                ? trim((string) $data['display_name'])
                : null,
            'display_unit' => filled($data['display_unit'] ?? null)
                ? trim((string) $data['display_unit'])
                : null,
            'display_decimals' => (int) $data['display_decimals'],
            'warning_low' => $data['warning_low'] ?? null,
            'warning_high' => $data['warning_high'] ?? null,
            'alarm_enabled' => (bool) $data['alarm_enabled'],
            'blink_on_alarm' => (bool) $data['blink_on_alarm'],
            'chart_enabled' => (bool) $data['chart_enabled'],
            'display_color' => filled($data['display_color'] ?? null)
                ? trim((string) $data['display_color'])
                : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã lưu cấu hình hiển thị và cảnh báo.',
            'tag' => $this->tagPayload($tag->fresh()),
        ]);
    }

    private function isSettingsRequest(Request $request): bool
    {
        return $request->hasAny([
            'display_name',
            'display_unit',
            'display_decimals',
            'warning_low',
            'warning_high',
            'alarm_enabled',
            'blink_on_alarm',
            'chart_enabled',
            'display_color',
        ]);
    }

    private function tagPayload(PlcTag $tag): array
    {
        return [
            'id' => $tag->id,
            'name' => $tag->display_name ?: $tag->source_name,
            'source_name' => $tag->source_name,
            'address' => $tag->address ?: $tag->source_key,
            'section_id' => $tag->monitoring_section_id,
            'row_id' => $tag->monitoring_row_id,
            'column_id' => $tag->monitoring_column_id,
            'display_unit' => $tag->display_unit,
            'display_decimals' => $tag->display_decimals,
            'warning_low' => $tag->warning_low,
            'warning_high' => $tag->warning_high,
            'alarm_enabled' => (bool) $tag->alarm_enabled,
            'blink_on_alarm' => (bool) $tag->blink_on_alarm,
            'chart_enabled' => (bool) $tag->chart_enabled,
            'display_color' => $tag->display_color,
        ];
    }
}
