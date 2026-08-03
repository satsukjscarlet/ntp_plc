<?php

namespace App\Services\Monitoring;

use App\Models\Machine;
use App\Models\PlcTag;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class MonitoringService
{
    private const ONLINE_AFTER_SECONDS = 15;
    private const WARNING_AFTER_SECONDS = 60;

    public function getMachineSnapshot(string $machineCode): array
    {
        $machine = Machine::query()
            ->where('code', $machineCode)
            ->firstOrFail();

        $plcIds = $machine->plcs()->pluck('id');

        /** @var Collection<int, PlcTag> $tags */
        $tags = PlcTag::query()
            ->with('currentValue')
            ->whereIn('plc_id', $plcIds)
            ->where('is_active', true)
            ->where('is_visible', true)
            ->orderByRaw("
                CASE
                    WHEN display_group IS NULL OR display_group = '' THEN 1
                    ELSE 0
                END
            ")
            ->orderBy('display_group')
            ->orderBy('display_order')
            ->orderByRaw("
                COALESCE(NULLIF(display_name, ''), source_name)
            ")
            ->get();

        $latestCollectedAt = $tags
            ->map(fn (PlcTag $tag) => $this->extractCollectedAt($tag))
            ->filter()
            ->sortDesc()
            ->first();

        $machineStatus = $this->buildMachineStatus($latestCollectedAt);

        $groups = $tags
            ->groupBy(fn (PlcTag $tag) => $tag->display_group ?: 'Chưa phân nhóm')
            ->map(function (Collection $groupTags, string $groupName): array {
                return [
                    'name' => $groupName,
                    'total_tags' => $groupTags->count(),
                    'tags' => $groupTags
                        ->map(fn (PlcTag $tag) => $this->transformTag($tag))
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();

        return [
            'machine' => [
                'id' => $machine->id,
                'code' => $machine->code,
                'name' => $machine->name,
                'status' => $machineStatus['status'],
                'status_label' => $machineStatus['label'],
                'last_seen_at' => $machineStatus['last_seen_at'],
                'age_seconds' => $machineStatus['age_seconds'],
            ],
            'collector' => [
                'status' => $machineStatus['status'],
                'total_tags' => $tags->count(),
                'online_after_seconds' => self::ONLINE_AFTER_SECONDS,
                'warning_after_seconds' => self::WARNING_AFTER_SECONDS,
            ],
            'groups' => $groups,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    private function transformTag(PlcTag $tag): array
    {
        $current = $tag->currentValue;
        $rawValue = $this->extractCurrentValue($tag);
        $collectedAt = $this->extractCollectedAt($tag);
        $quality = strtoupper((string) (
            $current?->quality
            ?? $current?->value_quality
            ?? 'GOOD'
        ));

        $ageSeconds = $collectedAt
            ? max(0, $collectedAt->diffInSeconds(now()))
            : null;

        $isStale = $ageSeconds === null
            || $ageSeconds > self::WARNING_AFTER_SECONDS;

        $alarmState = $this->resolveAlarmState(
            value: $rawValue,
            quality: $quality,
            isStale: $isStale,
            alarmLow: $tag->alarm_low,
            alarmHigh: $tag->alarm_high,
        );

        return [
            'id' => $tag->id,
            'plc_id' => $tag->plc_id,
            'source_key' => $tag->source_key,
            'source_name' => $tag->source_name,
            'display_name' => $tag->display_name ?: $tag->source_name,
            'address' => $tag->address,
            'data_type' => $tag->data_type,
            'display_group' => $tag->display_group ?: 'Chưa phân nhóm',
            'display_order' => (int) ($tag->display_order ?? 0),
            'value' => $rawValue,
            'formatted_value' => $this->formatValue(
                value: $rawValue,
                dataType: $tag->data_type,
                decimalPlaces: (int) ($tag->decimal_places ?? 1),
                unit: $tag->unit,
            ),
            'unit' => $tag->unit,
            'quality' => $quality,
            'alarm_state' => $alarmState,
            'is_stale' => $isStale,
            'age_seconds' => $ageSeconds,
            'collected_at' => $collectedAt?->toIso8601String(),
            'history_enabled' => (bool) $tag->history_enabled,
            'history_deadband' => (float) ($tag->history_deadband ?? 0),
            'alarm_low' => $tag->alarm_low !== null
                ? (float) $tag->alarm_low
                : null,
            'alarm_high' => $tag->alarm_high !== null
                ? (float) $tag->alarm_high
                : null,
        ];
    }

    private function extractCurrentValue(PlcTag $tag): mixed
    {
        $current = $tag->currentValue;

        if (!$current) {
            return null;
        }

        foreach ([
            'value',
            'value_numeric',
            'value_number',
            'value_float',
            'value_text',
            'value_string',
            'raw_value',
        ] as $column) {
            $value = $current->getAttribute($column);

            if ($value !== null && $value !== '') {
                return $this->normalizeValue($value, $tag->data_type);
            }
        }

        return null;
    }

    private function normalizeValue(mixed $value, ?string $dataType): mixed
    {
        $type = strtoupper((string) $dataType);

        if ($type === 'BOOL' || $type === 'BOOLEAN') {
            if (is_bool($value)) {
                return $value;
            }

            return in_array(
                strtolower(trim((string) $value)),
                ['1', 'true', 'on', 'yes'],
                true
            );
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return $value;
    }

    private function extractCollectedAt(PlcTag $tag): ?CarbonInterface
    {
        $current = $tag->currentValue;

        if (!$current) {
            return null;
        }

        foreach ([
            'collected_at',
            'recorded_at',
            'received_at',
            'updated_at',
        ] as $column) {
            $value = $current->getAttribute($column);

            if ($value instanceof CarbonInterface) {
                return $value;
            }

            if ($value) {
                return Carbon::parse($value);
            }
        }

        return null;
    }

    private function buildMachineStatus(?CarbonInterface $latestCollectedAt): array
    {
        if (!$latestCollectedAt) {
            return [
                'status' => 'OFFLINE',
                'label' => 'Chưa có dữ liệu',
                'last_seen_at' => null,
                'age_seconds' => null,
            ];
        }

        $ageSeconds = max(0, $latestCollectedAt->diffInSeconds(now()));

        if ($ageSeconds <= self::ONLINE_AFTER_SECONDS) {
            $status = 'ONLINE';
            $label = 'Đang kết nối';
        } elseif ($ageSeconds <= self::WARNING_AFTER_SECONDS) {
            $status = 'WARNING';
            $label = 'Dữ liệu đang chậm';
        } else {
            $status = 'OFFLINE';
            $label = 'Mất kết nối';
        }

        return [
            'status' => $status,
            'label' => $label,
            'last_seen_at' => $latestCollectedAt->toIso8601String(),
            'age_seconds' => $ageSeconds,
        ];
    }

    private function resolveAlarmState(
        mixed $value,
        string $quality,
        bool $isStale,
        mixed $alarmLow,
        mixed $alarmHigh
    ): string {
        if ($value === null || $value === '') {
            return 'NO_DATA';
        }

        if ($quality !== '' && $quality !== 'GOOD') {
            return 'BAD';
        }

        if ($isStale) {
            return 'STALE';
        }

        if (is_numeric($value)) {
            $numericValue = (float) $value;

            if ($alarmHigh !== null && $numericValue > (float) $alarmHigh) {
                return 'HIGH';
            }

            if ($alarmLow !== null && $numericValue < (float) $alarmLow) {
                return 'LOW';
            }
        }

        return 'NORMAL';
    }

    private function formatValue(
        mixed $value,
        ?string $dataType,
        int $decimalPlaces,
        ?string $unit
    ): string {
        if ($value === null || $value === '') {
            return '—';
        }

        $type = strtoupper((string) $dataType);

        if (($type === 'BOOL' || $type === 'BOOLEAN') && is_bool($value)) {
            $formatted = $value ? 'ON' : 'OFF';
        } elseif (is_numeric($value)) {
            $formatted = number_format(
                (float) $value,
                max(0, min(6, $decimalPlaces)),
                '.',
                ''
            );
        } else {
            $formatted = (string) $value;
        }

        return trim($formatted . ($unit ? ' ' . $unit : ''));
    }
}
