<?php

namespace App\Console\Commands;

use App\Models\ShiftProductionDeclaration;
use App\Models\ShiftProductionDeclarationItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateProductionWeights extends Command
{
    protected $signature = 'production:recalculate-weights
                            {--declaration= : Chỉ tính lại một ID phiếu}
                            {--dry-run : Chỉ xem kết quả, không cập nhật database}';

    protected $description = 'Tính lại trọng lượng quy đổi theo đúng quy tắc ĐVT mét × chiều dài thực tế × g/m.';

    public function handle(): int
    {
        $declarationId = $this->option('declaration');
        $dryRun = (bool) $this->option('dry-run');

        $query = ShiftProductionDeclarationItem::query()
            ->with('declaration')
            ->orderBy('id');

        if ($declarationId) {
            $query->where('shift_production_declaration_id', (int) $declarationId);
        }

        $items = $query->get();

        if ($items->isEmpty()) {
            $this->warn('Không có dòng sản phẩm nào cần tính lại.');
            return self::SUCCESS;
        }

        $changed = 0;

        DB::transaction(function () use ($items, $dryRun, &$changed): void {
            foreach ($items as $item) {
                $lengthM = (float) (
                    $item->actual_length_m
                    ?: $item->default_length_m_snapshot
                    ?: 0
                );

                $totalKg = $this->quantityToKg(
                    (float) $item->total_quantity,
                    $item->unit_snapshot,
                    (float) $item->standard_weight_g_snapshot,
                    $lengthM
                );

                $goodKg = $this->quantityToKg(
                    (float) $item->good_quantity,
                    $item->unit_snapshot,
                    (float) $item->standard_weight_g_snapshot,
                    $lengthM
                );

                $rejectKg = $this->quantityToKg(
                    (float) $item->reject_quantity,
                    $item->unit_snapshot,
                    (float) $item->standard_weight_g_snapshot,
                    $lengthM
                );

                $newValues = [
                    'converted_total_kg' => round((float) ($totalKg ?? 0), 3),
                    'converted_good_kg' => round((float) ($goodKg ?? 0), 3),
                    'converted_reject_kg' => round((float) ($rejectKg ?? 0), 3),
                    'converted_scrap_kg' => round((float) $item->scrap_quantity, 3),
                ];

                $oldTotal = (float) $item->converted_total_kg;
                $newTotal = (float) $newValues['converted_total_kg'];

                if (abs($oldTotal - $newTotal) < 0.0005) {
                    continue;
                }

                $changed++;

                $this->line(sprintf(
                    'Item #%d | Phiếu #%d | %s | %.3f kg → %.3f kg',
                    $item->id,
                    $item->shift_production_declaration_id,
                    $item->product_code_snapshot,
                    $oldTotal,
                    $newTotal
                ));

                if (!$dryRun) {
                    $item->update($newValues);
                }
            }

            if ($dryRun) {
                DB::rollBack();
            }
        });

        if ($dryRun) {
            $this->info("Dry-run hoàn tất: {$changed} dòng sẽ được cập nhật.");
        } else {
            $this->info("Đã cập nhật {$changed} dòng sản phẩm.");
        }

        return self::SUCCESS;
    }

    private function quantityToKg(
        float $quantity,
        ?string $unit,
        float $standardWeightG,
        float $actualLengthM
    ): ?float {
        $normalizedUnit = mb_strtolower(trim((string) $unit));
        $normalizedUnit = str_replace([' ', '.', '́'], '', $normalizedUnit);

        if (in_array($normalizedUnit, ['kg', 'kilogram', 'kilôgam'], true)) {
            return $quantity;
        }

        if (in_array($normalizedUnit, ['t', 'tan', 'tấn', 'ton', 'tonne'], true)) {
            return $quantity * 1000;
        }

        if (in_array($normalizedUnit, ['m', 'met', 'mét', 'meter', 'metre'], true)) {
            return $standardWeightG > 0 && $actualLengthM > 0
                ? $quantity * $actualLengthM * $standardWeightG / 1000
                : null;
        }

        return $standardWeightG > 0
            ? $quantity * $standardWeightG / 1000
            : null;
    }
}
