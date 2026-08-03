<?php

namespace App\Services;

use App\Models\ShiftProductionDeclaration;
use Illuminate\Support\Facades\DB;

class OeeCalculationService
{
    public function calculate(
        ShiftProductionDeclaration $declaration,
        ?array $plcMetrics = null
    ): ShiftProductionDeclaration {
        $declaration->loadMissing(['items', 'downtimeEntries.reason']);

        $blockingReasons = [];

        $availability = $plcMetrics['availability'] ?? null;
        $performance = $plcMetrics['performance'] ?? null;
        $runtimeMinutes = $plcMetrics['availability_runtime_minutes'] ?? null;

        if ($availability === null) {
            $blockingReasons[] = 'Không tính được A: chưa có giá trị MD488 hợp lệ trong ca kế tiếp.';
        }

        if (($plcMetrics['md264']['value'] ?? null) === null) {
            $blockingReasons[] = 'Không tính được P: chưa có giá trị MD264 hợp lệ trong chính ca.';
        } elseif (($plcMetrics['standard_rate'] ?? null) === null) {
            $blockingReasons[] = 'Không tính được P: chưa có năng suất định mức kg/h hợp lệ.';
        } elseif ($performance === null) {
            $blockingReasons[] = 'Không tính được P theo công thức MD264 / năng suất định mức.';
        }

        $totalQuantity = (float) $declaration->items->sum('total_quantity');
        $goodQuantity = (float) $declaration->items->sum('good_quantity');
        $rejectQuantity = (float) $declaration->items->sum('reject_quantity');
        $scrapKg = (float) $declaration->items->sum('scrap_quantity');

        if ($totalQuantity <= 0) {
            $quality = null;
            $blockingReasons[] = 'Không tính được Q: tổng sản lượng phải lớn hơn 0.';
        } else {
            $quality = $this->ratio($goodQuantity, $totalQuantity);
        }

        if (abs(($goodQuantity + $rejectQuantity) - $totalQuantity) > 0.0005) {
            $blockingReasons[] = sprintf(
                'Dữ liệu sản lượng không khớp: Tổng %.3f phải bằng Chính phẩm %.3f + Phế phẩm %.3f.',
                $totalQuantity,
                $goodQuantity,
                $rejectQuantity
            );
        }

        $oee = (
            $availability !== null
            && $performance !== null
            && $quality !== null
            && $blockingReasons === []
        )
            ? round($availability * $performance * $quality, 4)
            : null;

        $notes = [];

        if (!empty($plcMetrics['note'])) {
            $notes[] = $plcMetrics['note'];
        }

        $notes[] = sprintf(
            'Dữ liệu nhập: Tổng %.3f ĐVT; Chính phẩm %.3f ĐVT; Phế phẩm %.3f ĐVT; Phế liệu %.3f kg.',
            $totalQuantity,
            $goodQuantity,
            $rejectQuantity,
            $scrapKg
        );

        $notes[] = 'A = MD488 (Giờ chạy ca trước, đọc trong ca kế tiếp) / tổng thời gian ca.';
        $notes[] = 'P = MD264 trung bình trong chính ca / năng suất định mức máy-sản phẩm kg/h.';
        $notes[] = 'Q = Chính phẩm / Tổng sản lượng. Phế liệu kg không tham gia Q.';

        if ($blockingReasons !== []) {
            $notes[] = 'CHƯA TÍNH ĐƯỢC OEE: '
                . implode(' | ', array_values(array_unique($blockingReasons)));
        } else {
            $notes[] = sprintf(
                'Kết quả: A %.2f%%; P %.2f%%; Q %.2f%%; OEE %.2f%%.',
                $availability * 100,
                $performance * 100,
                $quality * 100,
                $oee * 100
            );
        }

        $plannedMinutes = max((float) $declaration->planned_production_minutes, 0);

        DB::transaction(function () use (
            $declaration,
            $availability,
            $performance,
            $quality,
            $oee,
            $runtimeMinutes,
            $plannedMinutes,
            $goodQuantity,
            $rejectQuantity,
            $totalQuantity,
            $notes
        ): void {
            $declaration->update([
                'good_quantity' => $goodQuantity,
                'reject_quantity' => $rejectQuantity,
                'total_quantity' => $totalQuantity,
                'plc_runtime_minutes' => $runtimeMinutes,
                'availability' => $availability,
                'performance' => $performance,
                'quality_rate' => $quality,
                'oee' => $oee,
                'unplanned_downtime_minutes' => $runtimeMinutes !== null
                    ? max($plannedMinutes - $runtimeMinutes, 0)
                    : null,
                'calculation_note' => implode(' ', $notes),
            ]);
        });

        return $declaration->fresh(['items', 'downtimeEntries.reason']);
    }

    private function ratio(float $numerator, float $denominator): float
    {
        if ($denominator <= 0) {
            return 0.0;
        }

        return round(min(max($numerator / $denominator, 0), 1), 4);
    }
}
