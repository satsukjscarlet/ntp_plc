<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTagMonitoringPositionRequest;
use App\Models\Machine;
use App\Models\MonitoringLayoutColumn;
use App\Models\MonitoringLayoutRow;
use App\Models\MonitoringLayoutSection;
use App\Models\PlcTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonitoringLayoutController extends Controller
{
    public function index(string $machineCode): View
    {
        $machine = Machine::query()
            ->where('code', $machineCode)
            ->firstOrFail();

        $sections = MonitoringLayoutSection::query()
            ->with([
                'rows' => fn ($query) => $query->where('is_active', true),
                'columns' => fn ($query) => $query->where('is_active', true),
            ])
            ->where('machine_id', $machine->id)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();

        $plcIds = $machine->plcs()->pluck('id');

        $tags = PlcTag::query()
            ->with([
                'monitoringSection:id,name_vi',
                'monitoringRow:id,name_vi',
                'monitoringColumn:id,name_vi',
            ])
            ->whereIn('plc_id', $plcIds)
            ->orderByRaw("COALESCE(NULLIF(display_name, ''), source_name)")
            ->paginate(50);

        return view('machines.monitoring-layout.index', compact(
            'machine',
            'sections',
            'tags'
        ));
    }

    public function updateTag(
        UpdateTagMonitoringPositionRequest $request,
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

        $sectionId = $request->integer('monitoring_section_id') ?: null;
        $rowId = $request->integer('monitoring_row_id') ?: null;
        $columnId = $request->integer('monitoring_column_id') ?: null;

        if ($sectionId === null) {
            $tag->update([
                'monitoring_section_id' => null,
                'monitoring_row_id' => null,
                'monitoring_column_id' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã bỏ vị trí hiển thị của tag.',
            ]);
        }

        $section = MonitoringLayoutSection::query()
            ->where('machine_id', $machine->id)
            ->findOrFail($sectionId);

        $row = $rowId
            ? MonitoringLayoutRow::query()
                ->where('section_id', $section->id)
                ->findOrFail($rowId)
            : null;

        $column = $columnId
            ? MonitoringLayoutColumn::query()
                ->where('section_id', $section->id)
                ->findOrFail($columnId)
            : null;

        if ($section->code === 'temperature' && (!$row || !$column)) {
            return response()->json([
                'success' => false,
                'message' => 'Thông số nhiệt bắt buộc phải chọn cả dòng và cột.',
            ], 422);
        }

        $duplicate = PlcTag::query()
            ->where('plc_id', $tag->plc_id)
            ->where('monitoring_section_id', $section->id)
            ->when($row, fn ($query) => $query->where('monitoring_row_id', $row->id))
            ->when(!$row, fn ($query) => $query->whereNull('monitoring_row_id'))
            ->when($column, fn ($query) => $query->where('monitoring_column_id', $column->id))
            ->when(!$column, fn ($query) => $query->whereNull('monitoring_column_id'))
            ->whereKeyNot($tag->id)
            ->first();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => 'Vị trí này đã được gán cho tag: '
                    . ($duplicate->display_name ?: $duplicate->source_name),
            ], 422);
        }

        $tag->update([
            'monitoring_section_id' => $section->id,
            'monitoring_row_id' => $row?->id,
            'monitoring_column_id' => $column?->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã lưu vị trí hiển thị cho tag.',
            'position' => [
                'section' => $section->name_vi,
                'row' => $row?->name_vi,
                'column' => $column?->name_vi,
            ],
        ]);
    }

    public function storeRow(Request $request, string $machineCode): RedirectResponse
    {
        $validated = $request->validate([
            'section_id' => ['required', 'integer', 'exists:monitoring_layout_sections,id'],
            'code' => ['required', 'string', 'max:80'],
            'name_vi' => ['required', 'string', 'max:150'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ], [
            'code.required' => 'Bạn chưa nhập mã dòng.',
            'name_vi.required' => 'Bạn chưa nhập tên dòng bằng tiếng Việt.',
        ]);

        $machine = Machine::query()->where('code', $machineCode)->firstOrFail();

        $section = MonitoringLayoutSection::query()
            ->where('machine_id', $machine->id)
            ->findOrFail($validated['section_id']);

        $section->rows()->create([
            'code' => $validated['code'],
            'name_vi' => $validated['name_vi'],
            'display_order' => $validated['display_order'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', 'Đã thêm dòng hiển thị.');
    }

    public function storeColumn(Request $request, string $machineCode): RedirectResponse
    {
        $validated = $request->validate([
            'section_id' => ['required', 'integer', 'exists:monitoring_layout_sections,id'],
            'code' => ['required', 'string', 'max:80'],
            'name_vi' => ['required', 'string', 'max:150'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ], [
            'code.required' => 'Bạn chưa nhập mã cột.',
            'name_vi.required' => 'Bạn chưa nhập tên cột bằng tiếng Việt.',
        ]);

        $machine = Machine::query()->where('code', $machineCode)->firstOrFail();

        $section = MonitoringLayoutSection::query()
            ->where('machine_id', $machine->id)
            ->findOrFail($validated['section_id']);

        $section->columns()->create([
            'code' => $validated['code'],
            'name_vi' => $validated['name_vi'],
            'display_order' => $validated['display_order'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', 'Đã thêm cột hiển thị.');
    }
}
