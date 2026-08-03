<?php

namespace App\Services;

use App\Models\MachineOeeSetting;
use App\Models\PlcTag;
use App\Models\PlcTagValue;
use App\Models\ProductionShift;
use App\Models\ShiftProductionDeclaration;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PlcOeeMetricService
{
    public function __construct(
        private readonly ShiftTimeService $shiftTimeService
    ) {
    }

    public function collect(ShiftProductionDeclaration $declaration): array
    {
        $declaration->loadMissing(['machine.plcs', 'shift', 'items']);

        $setting = MachineOeeSetting::query()
            ->where('machine_id', $declaration->machine_id)
            ->where('is_active', true)
            ->first();

        if (!$setting) {
            return [
                'availability' => null,
                'performance' => null,
                'availability_runtime_minutes' => null,
                'md488' => $this->failure('Máy chưa có cấu hình OEE đang hoạt động.'),
                'md264' => $this->failure('Máy chưa có cấu hình OEE đang hoạt động.'),
                'note' => 'Máy chưa có cấu hình OEE đang hoạt động trong machine_oee_settings.',
            ];
        }

        $availabilityTag = $this->resolveTag(
            $declaration,
            $setting->availability_tag_id,
            '%MD488'
        );

        $performanceTag = $this->resolveTag(
            $declaration,
            $setting->performance_tag_id,
            '%MD264'
        );

        $md488 = $this->availabilityMetric(
            $declaration,
            $availabilityTag,
            $setting->availability_value_unit ?: 'hours'
        );

        $md264 = $this->performanceMetric(
            $declaration,
            $performanceTag,
            $setting->performance_aggregation ?: 'stable_average',
            (float) ($setting->performance_stability_tolerance ?? 1.0),
            (int) ($setting->performance_stability_min_seconds ?? 60),
            (float) ($setting->performance_min_valid_value ?? 0.0)
        );

        $standard = $this->weightedStandardRate($declaration);
        $performance = null;

        if ($md264['value'] !== null && $standard['value'] !== null && $standard['value'] > 0) {
            $performance = $this->ratio($md264['value'], $standard['value']);
        }

        $notes = [
            $md488['note'],
            $md264['note'],
            $standard['note'],
        ];

        return [
            'availability' => $md488['availability'],
            'performance' => $performance,
            'availability_runtime_minutes' => $md488['runtime_minutes'],
            'md488' => $md488,
            'md264' => $md264,
            'standard_rate' => $standard['value'],
            'note' => implode(' ', array_filter($notes)),
        ];
    }

    private function availabilityMetric(
        ShiftProductionDeclaration $declaration,
        ?PlcTag $tag,
        string $unit
    ): array {
        if (!$tag) {
            return $this->failure(
                'Không tìm thấy tag MD488 cho máy. Hãy chọn tag Availability trong Cấu hình OEE.'
            );
        }

        [$from, $to, $sourceShift] = $this->nextShiftRange($declaration);

        $values = $this->validValues($tag->id, $from, $to);

        if ($values->isEmpty()) {
            return $this->failure(sprintf(
                'Không có dữ liệu MD488 "%s" (ID %d) trong ca kế tiếp %s, từ %s đến %s.',
                $this->tagName($tag),
                $tag->id,
                $sourceShift->name,
                $from->format('d/m/Y H:i:s'),
                $to->format('d/m/Y H:i:s')
            ), [
                'tag_id' => $tag->id,
                'from' => $from,
                'to' => $to,
                'sample_count' => 0,
            ]);
        }

        // MD488 có ý nghĩa "Giờ chạy ca trước", vì vậy lấy mẫu hợp lệ cuối cùng
        // trong ca kế tiếp. Nếu PLC giữ giá trị ổn định thì mẫu đầu/cuối giống nhau.
        $last = $values->last();
        $rawValue = (float) $last['value'];
        $runtimeMinutes = $this->toMinutes($rawValue, $unit);

        $shiftMinutes = max(
            Carbon::parse($declaration->shift_started_at)
                ->diffInMinutes(Carbon::parse($declaration->shift_ended_at)),
            0
        );

        $availability = $runtimeMinutes !== null && $shiftMinutes > 0
            ? $this->ratio($runtimeMinutes, $shiftMinutes)
            : null;

        return [
            'ok' => $availability !== null,
            'tag_id' => $tag->id,
            'tag_name' => $this->tagName($tag),
            'value' => $rawValue,
            'value_unit' => $unit,
            'runtime_minutes' => $runtimeMinutes,
            'availability' => $availability,
            'sample_count' => $values->count(),
            'min' => $values->min('value'),
            'max' => $values->max('value'),
            'avg' => $values->avg('value'),
            'first_at' => $values->first()['collected_at'],
            'last_at' => $values->last()['collected_at'],
            'from' => $from,
            'to' => $to,
            'note' => sprintf(
                'A dùng MD488 "%s" (ID %d) của ca kế tiếp %s. Có %d mẫu hợp lệ; '
                . 'Min %.4f; Max %.4f; Trung bình %.4f; mẫu cuối %s = %.4f %s. '
                . 'Quy đổi Runtime %.2f phút; thời gian ca %.2f phút; A %s.',
                $this->tagName($tag),
                $tag->id,
                $sourceShift->name,
                $values->count(),
                (float) $values->min('value'),
                (float) $values->max('value'),
                (float) $values->avg('value'),
                Carbon::parse($last['collected_at'])->format('d/m/Y H:i:s'),
                $rawValue,
                $this->unitLabel($unit),
                (float) $runtimeMinutes,
                (float) $shiftMinutes,
                $availability !== null
                    ? number_format($availability * 100, 2, '.', '') . '%'
                    : 'chưa tính được'
            ),
        ];
    }

    private function performanceMetric(
        ShiftProductionDeclaration $declaration,
        ?PlcTag $tag,
        string $aggregation,
        float $stabilityTolerance,
        int $stabilityMinSeconds,
        float $minValidValue
    ): array {
        if (!$tag) {
            return $this->failure(
                'Không tìm thấy tag MD264 cho máy. Hãy chọn tag Performance trong Cấu hình OEE.'
            );
        }

        $from = Carbon::parse($declaration->shift_started_at);
        $to = Carbon::parse($declaration->shift_ended_at);
        $values = $this->validValues($tag->id, $from, $to);

        if ($values->isEmpty()) {
            return $this->failure(sprintf(
                'Không có dữ liệu MD264 "%s" (ID %d) trong chính ca từ %s đến %s.',
                $this->tagName($tag),
                $tag->id,
                $from->format('d/m/Y H:i:s'),
                $to->format('d/m/Y H:i:s')
            ), [
                'tag_id' => $tag->id,
                'from' => $from,
                'to' => $to,
                'sample_count' => 0,
            ]);
        }

        $positiveValues = $values
            ->filter(fn (array $row): bool => (float) $row['value'] > $minValidValue)
            ->values();

        if ($positiveValues->isEmpty()) {
            return $this->failure(sprintf(
                'MD264 có %d mẫu nhưng không có mẫu nào lớn hơn ngưỡng hợp lệ %.4f kg/h.',
                $values->count(),
                $minValidValue
            ), [
                'tag_id' => $tag->id,
                'sample_count' => $values->count(),
            ]);
        }

        $stable = $this->stableSegments(
            $positiveValues,
            $stabilityTolerance,
            $stabilityMinSeconds
        );

        $value = match ($aggregation) {
            'last' => (float) $positiveValues->last()['value'],
            'maximum' => (float) $positiveValues->max('value'),
            'average' => (float) $positiveValues->avg('value'),
            default => $stable['average'],
        };

        $ok = $value !== null;

        $note = sprintf(
            'P dùng MD264 "%s" (ID %d) trong chính ca. Tổng %d mẫu hợp lệ; '
            . 'loại %d mẫu <= %.4f kg/h; Min %.4f; Max %.4f; Trung bình toàn bộ %.4f. ',
            $this->tagName($tag),
            $tag->id,
            $values->count(),
            $values->count() - $positiveValues->count(),
            $minValidValue,
            (float) $positiveValues->min('value'),
            (float) $positiveValues->max('value'),
            (float) $positiveValues->avg('value')
        );

        if ($aggregation === 'stable_average') {
            $note .= sprintf(
                'Ổn định được xác định khi chênh lệch giữa hai mẫu liên tiếp không vượt quá ±%.4f kg/h '
                . 'trong tối thiểu %d giây. Tìm thấy %d khoảng ổn định, gồm %d mẫu trên tổng %d mẫu dương; '
                . 'năng suất ổn định trung bình %s kg/h.',
                $stabilityTolerance,
                $stabilityMinSeconds,
                $stable['segment_count'],
                $stable['sample_count'],
                $positiveValues->count(),
                $stable['average'] !== null
                    ? number_format($stable['average'], 4, '.', '')
                    : 'chưa tính được'
            );
        } else {
            $note .= sprintf(
                'Giá trị dùng theo "%s" = %.4f kg/h.',
                $this->aggregationLabel($aggregation),
                (float) $value
            );
        }

        if (!$ok) {
            $note .= ' Không có khoảng ổn định đủ thời gian; hãy giảm thời gian tối thiểu hoặc kiểm tra dữ liệu MD264.';
        }

        return [
            'ok' => $ok,
            'tag_id' => $tag->id,
            'tag_name' => $this->tagName($tag),
            'value' => $value,
            'aggregation' => $aggregation,
            'sample_count' => $values->count(),
            'positive_sample_count' => $positiveValues->count(),
            'stable_sample_count' => $stable['sample_count'],
            'stable_segment_count' => $stable['segment_count'],
            'stability_tolerance' => $stabilityTolerance,
            'stability_min_seconds' => $stabilityMinSeconds,
            'min_valid_value' => $minValidValue,
            'min' => $positiveValues->min('value'),
            'max' => $positiveValues->max('value'),
            'avg' => $positiveValues->avg('value'),
            'first_at' => $positiveValues->first()['collected_at'],
            'last_at' => $positiveValues->last()['collected_at'],
            'from' => $from,
            'to' => $to,
            'note' => $note,
        ];
    }

    private function stableSegments(
        Collection $values,
        float $tolerance,
        int $minimumSeconds
    ): array {
        if ($values->count() < 2) {
            return [
                'average' => null,
                'sample_count' => 0,
                'segment_count' => 0,
            ];
        }

        $segments = [];
        $current = [$values->first()];
        $previous = $values->first();

        foreach ($values->slice(1) as $row) {
            $difference = abs((float) $row['value'] - (float) $previous['value']);
            $gapSeconds = Carbon::parse($previous['collected_at'])
                ->diffInSeconds(Carbon::parse($row['collected_at']));

            if ($difference <= $tolerance && $gapSeconds <= 60) {
                $current[] = $row;
            } else {
                $segments[] = $current;
                $current = [$row];
            }

            $previous = $row;
        }

        $segments[] = $current;

        $stableSegments = collect($segments)->filter(function (array $segment) use ($minimumSeconds): bool {
            if (count($segment) < 2) {
                return false;
            }

            $first = Carbon::parse($segment[0]['collected_at']);
            $last = Carbon::parse($segment[array_key_last($segment)]['collected_at']);

            return $first->diffInSeconds($last) >= $minimumSeconds;
        })->values();

        $stableValues = $stableSegments
            ->flatMap(fn (array $segment): array => $segment)
            ->values();

        return [
            'average' => $stableValues->isNotEmpty()
                ? (float) $stableValues->avg('value')
                : null,
            'sample_count' => $stableValues->count(),
            'segment_count' => $stableSegments->count(),
        ];
    }

    private function weightedStandardRate(ShiftProductionDeclaration $declaration): array
    {
        if ($declaration->items->isEmpty()) {
            return [
                'value' => null,
                'note' => 'Không tính được P: phiếu chưa có sản phẩm.',
            ];
        }

        $unsupported = $declaration->items->filter(function ($item): bool {
            return $this->normalizeRateUnit((string) $item->rate_unit_snapshot) !== 'kg/h';
        });

        if ($unsupported->isNotEmpty()) {
            return [
                'value' => null,
                'note' => 'Không tính được P theo MD264 kg/h: tất cả định mức máy-sản phẩm trong phiếu phải dùng đơn vị kg/h.',
            ];
        }

        $valid = $declaration->items->filter(
            fn ($item): bool =>
                (float) $item->standard_rate_snapshot > 0
                && (float) $item->converted_total_kg > 0
        );

        if ($valid->isEmpty()) {
            return [
                'value' => null,
                'note' => 'Không tính được P: chưa có định mức kg/h hoặc trọng lượng quy đổi hợp lệ.',
            ];
        }

        $totalWeight = (float) $valid->sum('converted_total_kg');
        $weighted = $valid->sum(function ($item): float {
            return (float) $item->standard_rate_snapshot
                * (float) $item->converted_total_kg;
        });

        $rate = $totalWeight > 0 ? $weighted / $totalWeight : null;

        return [
            'value' => $rate,
            'note' => sprintf(
                'Năng suất định mức dùng tính P là trung bình gia quyền theo trọng lượng của %d sản phẩm: %.4f kg/h.',
                $valid->count(),
                (float) $rate
            ),
        ];
    }

    private function nextShiftRange(
        ShiftProductionDeclaration $declaration
    ): array {
        $shifts = ProductionShift::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $currentIndex = $shifts->search(
            fn (ProductionShift $shift): bool =>
                $shift->id === $declaration->production_shift_id
        );

        if ($currentIndex === false || $shifts->isEmpty()) {
            $from = Carbon::parse($declaration->shift_ended_at);
            return [
                $from,
                $from->copy()->addHours(8),
                $declaration->shift,
            ];
        }

        $nextIndex = $currentIndex + 1;
        $nextDate = Carbon::parse($declaration->production_date);

        if ($nextIndex >= $shifts->count()) {
            $nextIndex = 0;
            $nextDate->addDay();
        }

        $nextShift = $shifts[$nextIndex];
        [$from, $to] = $this->shiftTimeService->range($nextShift, $nextDate);

        return [$from, $to, $nextShift];
    }

    private function resolveTag(
        ShiftProductionDeclaration $declaration,
        ?int $configuredTagId,
        string $fallbackAddress
    ): ?PlcTag {
        if ($configuredTagId) {
            return PlcTag::query()->find($configuredTagId);
        }

        $plcIds = $declaration->machine->plcs->pluck('id');

        return PlcTag::query()
            ->whereIn('plc_id', $plcIds)
            ->where('address', $fallbackAddress)
            ->first();
    }

    private function validValues(
        int $tagId,
        Carbon $from,
        Carbon $to
    ): Collection {
        return PlcTagValue::query()
            ->where('plc_tag_id', $tagId)
            ->where('collected_at', '>=', $from)
            ->where('collected_at', '<', $to)
            ->orderBy('collected_at')
            ->get(['numeric_value', 'raw_value', 'collected_at'])
            ->map(function (PlcTagValue $row): ?array {
                $value = $this->numericValue($row);

                if ($value === null) {
                    return null;
                }

                return [
                    'value' => $value,
                    'collected_at' => $row->collected_at,
                ];
            })
            ->filter()
            ->values();
    }

    private function numericValue(PlcTagValue $row): ?float
    {
        if ($row->numeric_value !== null && is_numeric($row->numeric_value)) {
            return (float) $row->numeric_value;
        }

        $raw = trim((string) $row->raw_value);

        return $raw !== '' && is_numeric($raw)
            ? (float) $raw
            : null;
    }

    private function toMinutes(float $value, string $unit): ?float
    {
        return match ($unit) {
            'minutes' => $value,
            'seconds' => $value / 60,
            default => $value * 60,
        };
    }

    private function normalizeRateUnit(string $unit): ?string
    {
        $unit = mb_strtolower(trim($unit));
        $unit = str_replace([' ', 'giờ', 'hour', 'hr'], ['', 'h', 'h', 'h'], $unit);

        return in_array($unit, ['kg/h', 'kgh'], true) ? 'kg/h' : null;
    }

    private function tagName(PlcTag $tag): string
    {
        return $tag->display_name
            ?: $tag->source_name
            ?: $tag->address
            ?: ('#' . $tag->id);
    }

    private function ratio(float $numerator, float $denominator): float
    {
        if ($denominator <= 0) {
            return 0.0;
        }

        return round(min(max($numerator / $denominator, 0), 1), 4);
    }

    private function unitLabel(string $unit): string
    {
        return match ($unit) {
            'minutes' => 'phút',
            'seconds' => 'giây',
            default => 'giờ',
        };
    }

    private function aggregationLabel(string $aggregation): string
    {
        return match ($aggregation) {
            'last' => 'mẫu cuối',
            'maximum' => 'giá trị lớn nhất',
            'average' => 'trung bình toàn bộ mẫu dương',
            default => 'trung bình các khoảng ổn định',
        };
    }

    private function failure(string $note, array $extra = []): array
    {
        return array_merge([
            'ok' => false,
            'value' => null,
            'availability' => null,
            'runtime_minutes' => null,
            'note' => $note,
        ], $extra);
    }
}
