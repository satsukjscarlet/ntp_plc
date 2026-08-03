<?php

namespace App\Services\Monitoring;

use App\Models\Machine;
use App\Models\MonitoringLayoutSection;
use App\Models\PlcTag;

class MonitoringLayoutDataService
{
    public function getLayout(string $machineCode): array
    {
        $machine = Machine::query()
            ->where('code', $machineCode)
            ->firstOrFail();

        $plcIds = $machine->plcs()->pluck('id');

        $sections = MonitoringLayoutSection::query()
            ->with([
                'rows' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('display_order'),
                'columns' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('display_order'),
            ])
            ->where('machine_id', $machine->id)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();

        $tags = PlcTag::query()
            ->with('currentValue')
            ->whereIn('plc_id', $plcIds)
            ->where('is_active', true)
            ->where('is_visible', true)
            ->whereNotNull('monitoring_section_id')
            ->get()
            ->keyBy(function (PlcTag $tag): string {
                return implode(':', [
                    $tag->monitoring_section_id ?: 0,
                    $tag->monitoring_row_id ?: 0,
                    $tag->monitoring_column_id ?: 0,
                ]);
            });

        return [
            'machine' => [
                'id' => $machine->id,
                'code' => $machine->code,
                'name' => $machine->name,
            ],
            'sections' => $sections->map(function ($section) use ($tags): array {
                return [
                    'id' => $section->id,
                    'code' => $section->code,
                    'name' => $section->name_vi,
                    'rows' => $section->rows->map(function ($row) use ($section, $tags): array {
                        return [
                            'id' => $row->id,
                            'code' => $row->code,
                            'name' => $row->name_vi,
                            'cells' => $section->columns->map(function ($column) use (
                                $section,
                                $row,
                                $tags
                            ): array {
                                $key = implode(':', [
                                    $section->id,
                                    $row->id,
                                    $column->id,
                                ]);

                                $tag = $tags->get($key);

                                return [
                                    'column_id' => $column->id,
                                    'column_code' => $column->code,
                                    'column_name' => $column->name_vi,
                                    'tag_id' => $tag?->id,
                                    'tag_name' => $tag
                                        ? ($tag->display_name ?: $tag->source_name)
                                        : null,
                                ];
                            })->values(),
                        ];
                    })->values(),
                ];
            })->values(),
        ];
    }
}
