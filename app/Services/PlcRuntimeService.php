<?php

namespace App\Services;

use App\Models\Machine;
use App\Models\MachineOeeSetting;
use App\Models\PlcTagValue;
use Carbon\Carbon;

class PlcRuntimeService
{
    public function calculateMinutes(Machine $machine, Carbon $startedAt, Carbon $endedAt): array
    {
        $setting = MachineOeeSetting::query()->with('runStatusTag')
            ->where('machine_id', $machine->id)->where('is_active', true)->first();

        if (!$setting) return $this->failure('Máy chưa có cấu hình OEE đang hoạt động trong machine_oee_settings.');
        if (!$setting->run_status_tag_id) return $this->failure('Cấu hình OEE của máy chưa chọn tag trạng thái chạy.');
        if (!$setting->runStatusTag) return $this->failure('Tag trạng thái chạy đã cấu hình không còn tồn tại. Tag ID: '.$setting->run_status_tag_id.'.');
        if ($endedAt->lessThanOrEqualTo($startedAt)) return $this->failure('Khoảng thời gian ca không hợp lệ.');

        $beforeStart = PlcTagValue::query()->where('plc_tag_id', $setting->run_status_tag_id)
            ->where('collected_at', '<', $startedAt)->latest('collected_at')
            ->first(['numeric_value', 'raw_value', 'collected_at']);

        $insideValues = PlcTagValue::query()->where('plc_tag_id', $setting->run_status_tag_id)
            ->whereBetween('collected_at', [$startedAt, $endedAt])->orderBy('collected_at')
            ->get(['numeric_value', 'raw_value', 'collected_at']);

        if ($insideValues->isEmpty() && !$beforeStart) {
            return $this->failure(sprintf('Không có dữ liệu lịch sử cho tag chạy "%s" (ID %d) trong ca %s đến %s.', $this->tagName($setting), $setting->run_status_tag_id, $startedAt->format('d/m/Y H:i:s'), $endedAt->format('d/m/Y H:i:s')));
        }

        $values = $insideValues;
        if ($beforeStart) {
            $beforeStart->collected_at = $startedAt->copy();
            $values = collect([$beforeStart])->concat($insideValues)->values();
        }

        $runtimeSeconds = 0; $gapLimit = max((int)$setting->collector_gap_limit_seconds, 1);
        $runningSamples = 0; $validSamples = 0; $invalidSamples = 0; $largestGap = 0;
        $largestGapFrom = null; $largestGapTo = null; $numericValues = [];
        $firstSamples = []; $lastSamples = [];

        for ($i = 0; $i < $values->count(); $i++) {
            $current = $values[$i];
            $currentAt = Carbon::parse($current->collected_at)->max($startedAt);
            $nextAt = $i + 1 < $values->count() ? Carbon::parse($values[$i + 1]->collected_at)->min($endedAt) : $endedAt->copy();
            $rawGap = max($currentAt->diffInSeconds($nextAt, false), 0);
            if ($rawGap > $largestGap) { $largestGap = $rawGap; $largestGapFrom = $currentAt->copy(); $largestGapTo = $nextAt->copy(); }
            $numeric = $this->numericValue($current);
            if ($numeric === null) { $invalidSamples++; continue; }

            $validSamples++; $numericValues[] = $numeric;
            $sample = ['at' => $currentAt->format('H:i:s'), 'value' => $numeric];
            if (count($firstSamples) < 10) $firstSamples[] = $sample;
            $lastSamples[] = $sample; if (count($lastSamples) > 10) array_shift($lastSamples);

            if ($this->isRunningNumeric($numeric, $setting)) {
                $runningSamples++; $runtimeSeconds += min($rawGap, $gapLimit);
            }
        }

        if ($validSamples === 0) return $this->failure(sprintf('Có %d bản ghi lịch sử của tag "%s" nhưng tất cả đều không có giá trị số hợp lệ.', $values->count(), $this->tagName($setting)));

        $minutes = round($runtimeSeconds / 60, 2);
        $min = min($numericValues); $max = max($numericValues); $avg = array_sum($numericValues) / count($numericValues);
        $notRunning = $validSamples - $runningSamples;
        $operator = $this->operatorLabel($setting->run_value_mode);
        $gapText = $largestGapFrom && $largestGapTo
            ? sprintf('%s → %s', $largestGapFrom->format('d/m/Y H:i:s'), $largestGapTo->format('d/m/Y H:i:s')) : 'không xác định';

        $note = sprintf(
            'Runtime PLC: tag "%s" (ID %d), điều kiện giá trị %s %s. Trong ca có %d mẫu; %d mẫu hợp lệ; %d mẫu chạy; %d mẫu không chạy; %d mẫu không hợp lệ. Thống kê giá trị: Min %.4f; Max %.4f; Trung bình %.4f. Mẫu đầu %s; mẫu cuối %s. Khoảng trống lớn nhất %d giây tại %s; giới hạn cộng mỗi khoảng %d giây. Runtime %.2f phút.',
            $this->tagName($setting), $setting->run_status_tag_id, $operator, $setting->run_value_threshold,
            $insideValues->count(), $validSamples, $runningSamples, $notRunning, $invalidSamples,
            $min, $max, $avg,
            Carbon::parse($values->first()->collected_at)->format('d/m/Y H:i:s'),
            Carbon::parse($values->last()->collected_at)->format('d/m/Y H:i:s'),
            $largestGap, $gapText, $gapLimit, $minutes
        );

        if ($minutes <= 0) {
            $note .= sprintf(' Không có khoảng nào thỏa điều kiện chạy. Giá trị lớn nhất thực tế là %.4f trong khi điều kiện là %s %s; hãy sửa mode/ngưỡng tại menu Cấu hình OEE.', $max, $operator, $setting->run_value_threshold);
        }

        return [
            'minutes' => $minutes, 'note' => $note, 'ok' => true,
            'sample_count' => $insideValues->count(), 'valid_sample_count' => $validSamples,
            'running_sample_count' => $runningSamples, 'not_running_sample_count' => $notRunning,
            'invalid_sample_count' => $invalidSamples, 'min' => $min, 'max' => $max, 'avg' => round($avg, 4),
            'largest_gap_seconds' => $largestGap, 'largest_gap_from' => $largestGapFrom, 'largest_gap_to' => $largestGapTo,
            'first_samples' => $firstSamples, 'last_samples' => $lastSamples,
        ];
    }

    private function failure(string $note): array
    {
        return ['minutes'=>null,'note'=>$note,'ok'=>false,'sample_count'=>0,'valid_sample_count'=>0,'running_sample_count'=>0];
    }

    private function tagName(MachineOeeSetting $setting): string
    {
        return $setting->runStatusTag?->display_name ?: $setting->runStatusTag?->source_name ?: ('#'.$setting->run_status_tag_id);
    }

    private function numericValue(PlcTagValue $value): ?float
    {
        if ($value->numeric_value !== null && is_numeric($value->numeric_value)) return (float)$value->numeric_value;
        if ($value->raw_value !== null && is_numeric(trim((string)$value->raw_value))) return (float)trim((string)$value->raw_value);
        return null;
    }

    private function isRunningNumeric(float $numeric, MachineOeeSetting $setting): bool
    {
        $threshold = (float)$setting->run_value_threshold;
        return match ($setting->run_value_mode) {
            'equals' => abs($numeric - $threshold) < 0.000001,
            'not_equals' => abs($numeric - $threshold) >= 0.000001,
            'greater_or_equal' => $numeric >= $threshold,
            'less_than' => $numeric < $threshold,
            'less_or_equal' => $numeric <= $threshold,
            default => $numeric > $threshold,
        };
    }

    private function operatorLabel(string $mode): string
    {
        return match ($mode) {
            'greater_or_equal' => '≥', 'less_than' => '<', 'less_or_equal' => '≤',
            'equals' => '=', 'not_equals' => '≠', default => '>',
        };
    }
}
