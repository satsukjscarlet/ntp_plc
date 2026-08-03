<?php

namespace App\Services;

use App\Models\Machine;
use App\Models\MonitoringLayoutSection;
use App\Models\PlcTag;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MonitoringRuntimeService
{
    public function buildLayout(Machine $machine): array
    {
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
            ->whereNotNull('monitoring_section_id')
            ->whereNotNull('monitoring_row_id')
            ->whereNotNull('monitoring_column_id')
            ->get();

        $currentValues = $this->loadCurrentValues($tags->pluck('id'));

        return [
            'machine' => [
                'id' => $machine->id,
                'code' => $machine->code,
                'name' => $machine->name,
            ],
            'generated_at' => now()->toIso8601String(),
            'sections' => $sections->map(function ($section) use (
                $tags,
                $currentValues
            ): array {
                $sectionTags = $tags
                    ->where('monitoring_section_id', $section->id)
                    ->keyBy(function (PlcTag $tag): string {
                        return $tag->monitoring_row_id
                            . ':'
                            . $tag->monitoring_column_id;
                    });

                return [
                    'id' => $section->id,
                    'code' => $section->code,
                    'name' => $section->name_vi,
                    'rows' => $section->rows->map(function ($row) use (
                        $section,
                        $sectionTags,
                        $currentValues
                    ): array {
                        return [
                            'id' => $row->id,
                            'code' => $row->code,
                            'name' => $row->name_vi,
                            'cells' => $section->columns->map(function (
                                $column
                            ) use (
                                $section,
                                $row,
                                $sectionTags,
                                $currentValues
                            ): array {
                                $tag = $sectionTags->get(
                                    $row->id . ':' . $column->id
                                );

                                return [
                                    'section_id' => $section->id,
                                    'row_id' => $row->id,
                                    'column_id' => $column->id,
                                    'column_code' => $column->code,
                                    'column_name' => $column->name_vi,
                                    'tag' => $tag
                                        ? $this->tagPayload(
                                            $tag,
                                            $currentValues->get($tag->id)
                                        )
                                        : null,
                                ];
                            })->values()->all(),
                        ];
                    })->values()->all(),
                    'columns' => $section->columns->map(fn ($column): array => [
                        'id' => $column->id,
                        'code' => $column->code,
                        'name' => $column->name_vi,
                    ])->values()->all(),
                ];
            })->values()->all(),
        ];
    }

    public function currentValues(Machine $machine): array
    {
        $plcIds = $machine->plcs()->pluck('id');

        $tags = PlcTag::query()
            ->whereIn('plc_id', $plcIds)
            ->whereNotNull('monitoring_section_id')
            ->get();

        $currentValues = $this->loadCurrentValues($tags->pluck('id'));

        $tagPayloads = $tags->mapWithKeys(function (PlcTag $tag) use (
            $currentValues
        ): array {
            return [
                (string) $tag->id => $this->tagPayload(
                    $tag,
                    $currentValues->get($tag->id)
                ),
            ];
        });

        $latestCollectedAt = $tagPayloads
            ->pluck('collected_at')
            ->filter()
            ->sortDesc()
            ->first();

        return [
            'machine_code' => $machine->code,
            'server_time' => now()->toIso8601String(),
            'latest_collected_at' => $latestCollectedAt,
            'connection' => $this->connectionPayload($latestCollectedAt),
            'summary' => $this->summaryPayload($tagPayloads),
            'tags' => $tagPayloads->all(),
        ];
    }

    private function summaryPayload(Collection $tags): array
    {
        $high = $tags->filter(
            fn (array $tag): bool =>
                ($tag['status']['code'] ?? null) === 'high'
        )->count();

        $low = $tags->filter(
            fn (array $tag): bool =>
                ($tag['status']['code'] ?? null) === 'low'
        )->count();

        $stale = $tags->filter(
            fn (array $tag): bool =>
                (bool) ($tag['is_stale'] ?? false)
        )->count();

        return [
            'total' => $tags->count(),
            'normal' => max($tags->count() - $high - $low, 0),
            'high' => $high,
            'low' => $low,
            'alarm' => $high + $low,
            'stale' => $stale,
        ];
    }

    private function connectionPayload(?string $collectedAt): array
    {
        if (!$collectedAt) {
            return [
                'code' => 'offline',
                'label' => 'Chưa có dữ liệu',
                'age_seconds' => null,
            ];
        }

        try {
            $ageSeconds = max(
                Carbon::parse($collectedAt)->diffInSeconds(now(), false),
                0
            );
        } catch (\Throwable) {
            return [
                'code' => 'offline',
                'label' => 'Thời gian dữ liệu không hợp lệ',
                'age_seconds' => null,
            ];
        }

        if ($ageSeconds <= 15) {
            return [
                'code' => 'online',
                'label' => 'Đang cập nhật dữ liệu',
                'age_seconds' => $ageSeconds,
            ];
        }

        if ($ageSeconds <= 60) {
            return [
                'code' => 'delayed',
                'label' => 'Dữ liệu cập nhật chậm',
                'age_seconds' => $ageSeconds,
            ];
        }

        return [
            'code' => 'offline',
            'label' => 'Mất kết nối hoặc collector đã dừng',
            'age_seconds' => $ageSeconds,
        ];
    }

    private function tagPayload(PlcTag $tag, ?array $current): array
    {
        $rawValue = $current['value'] ?? null;
        $numericValue = is_numeric($rawValue)
            ? (float) $rawValue
            : null;

        $status = $this->resolveStatus($tag, $numericValue);

        return [
            'id' => $tag->id,
            'name' => $tag->display_name ?: $tag->source_name,
            'source_name' => $tag->source_name,
            'address' => $tag->address ?: $tag->source_key,
            'raw_value' => $rawValue,
            'formatted_value' => $this->formatValue($tag, $rawValue),
            'numeric_value' => $numericValue,
            'unit' => $tag->display_unit,
            'status' => $status,
            'display_color' => $tag->display_color,
            'blink_on_alarm' => (bool) $tag->blink_on_alarm,
            'chart_enabled' => (bool) $tag->chart_enabled,
            'collected_at' => $current['collected_at'] ?? null,
            'is_stale' => $this->isStale($current['collected_at'] ?? null),
        ];
    }

    private function resolveStatus(
        PlcTag $tag,
        ?float $numericValue
    ): array {
        if (
            !$tag->alarm_enabled
            || $numericValue === null
        ) {
            return [
                'code' => 'normal',
                'label' => 'Bình thường',
            ];
        }

        if (
            $tag->warning_high !== null
            && $numericValue >= (float) $tag->warning_high
        ) {
            return [
                'code' => 'high',
                'label' => 'Vượt ngưỡng cao',
            ];
        }

        if (
            $tag->warning_low !== null
            && $numericValue <= (float) $tag->warning_low
        ) {
            return [
                'code' => 'low',
                'label' => 'Dưới ngưỡng thấp',
            ];
        }

        return [
            'code' => 'normal',
            'label' => 'Bình thường',
        ];
    }

    private function formatValue(PlcTag $tag, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '--';
        }

        if (is_numeric($value)) {
            $decimals = min(
                max((int) ($tag->display_decimals ?? 1), 0),
                6
            );

            return number_format(
                (float) $value,
                $decimals,
                '.',
                ''
            );
        }

        return (string) $value;
    }

    private function isStale(?string $collectedAt): bool
    {
        if (!$collectedAt) {
            return true;
        }

        try {
            return Carbon::parse($collectedAt)->lt(now()->subSeconds(15));
        } catch (\Throwable) {
            return true;
        }
    }

    private function loadCurrentValues(Collection $tagIds): Collection
    {
        if ($tagIds->isEmpty() || !Schema::hasTable('plc_current_values')) {
            return collect();
        }

        $columns = Schema::getColumnListing('plc_current_values');

        $tagColumn = $this->firstExisting(
            $columns,
            ['plc_tag_id', 'tag_id']
        );

        $timeColumn = $this->firstExisting(
            $columns,
            [
                'collected_at',
                'source_time',
                'received_at',
                'updated_at',
                'created_at',
                'recorded_at',
            ]
        );

        if (!$tagColumn) {
            return collect();
        }

        /*
         * Dự án hiện tại lưu dữ liệu theo schema:
         *
         * raw_value, numeric_value, boolean_value, string_value,
         * data_quality, collected_at.
         *
         * Collector hiện luôn cập nhật raw_value, vì vậy phải ưu tiên
         * raw_value. Phiên bản cũ chọn numeric_value cho toàn bộ bảng;
         * các tag BOOL hoặc các dòng chưa tách kiểu sẽ nhận NULL và
         * giao diện chỉ hiện "--".
         */
        $valueCandidates = array_values(array_filter([
            in_array('raw_value', $columns, true)
                ? 'raw_value'
                : null,
            in_array('numeric_value', $columns, true)
                ? 'numeric_value'
                : null,
            in_array('value_numeric', $columns, true)
                ? 'value_numeric'
                : null,
            in_array('boolean_value', $columns, true)
                ? 'boolean_value'
                : null,
            in_array('string_value', $columns, true)
                ? 'string_value'
                : null,
            in_array('text_value', $columns, true)
                ? 'text_value'
                : null,
            in_array('value_text', $columns, true)
                ? 'value_text'
                : null,
            in_array('value', $columns, true)
                ? 'value'
                : null,
        ]));

        if ($valueCandidates === []) {
            return collect();
        }

        $query = DB::table('plc_current_values')
            ->whereIn($tagColumn, $tagIds->all());

        $select = [
            $tagColumn . ' as tag_id',
        ];

        /*
         * Dùng COALESCE để tương thích nhiều phiên bản schema.
         * raw_value đứng đầu vì đây là cột đang được collector cập nhật
         * liên tục ở dự án hiện tại.
         */
        $wrappedValueColumns = array_map(
            fn (string $column): string =>
                $query->getGrammar()->wrap($column),
            $valueCandidates
        );

        $select[] = DB::raw(
            'COALESCE('
            . implode(', ', $wrappedValueColumns)
            . ') as current_value'
        );

        if ($timeColumn) {
            $select[] = $timeColumn . ' as collected_at';
        }

        $qualityColumn = $this->firstExisting(
            $columns,
            ['data_quality', 'quality']
        );

        if ($qualityColumn) {
            $select[] = $qualityColumn . ' as data_quality';
        }

        return $query
            ->select($select)
            ->get()
            ->mapWithKeys(function ($row): array {
                return [
                    (int) $row->tag_id => [
                        'value' => $row->current_value,
                        'collected_at' => $row->collected_at ?? null,
                        'data_quality' => $row->data_quality ?? null,
                    ],
                ];
            });
    }

    private function firstExisting(
        array $columns,
        array $candidates
    ): ?string {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }

        return null;
    }
}
