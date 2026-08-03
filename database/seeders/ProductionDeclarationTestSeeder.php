<?php

namespace Database\Seeders;

use App\Models\Machine;
use App\Models\MachineProductStandard;
use App\Models\Product;
use App\Models\ProductionShift;
use App\Models\ShiftProductionDeclaration;
use App\Models\User;
use App\Services\ShiftTimeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductionDeclarationTestSeeder extends Seeder
{
    public function run(): void
    {
        $machine = Machine::query()->where('code', 'M62')->first();
        $shift = ProductionShift::query()->where('code', 'CA1')->first()
            ?? ProductionShift::query()->orderBy('display_order')->first();
        $user = User::query()->orderBy('id')->first();

        if (!$machine || !$shift || !$user) {
            $this->command?->warn('Bỏ qua ProductionDeclarationTestSeeder: thiếu máy M62, ca hoặc người dùng.');
            return;
        }

        $standards = MachineProductStandard::query()
            ->with('product')
            ->where('machine_id', $machine->id)
            ->where('is_active', true)
            ->whereHas('product', fn ($query) => $query->where('is_active', true))
            ->limit(2)
            ->get();

        if ($standards->isEmpty()) {
            $this->command?->warn('Bỏ qua ProductionDeclarationTestSeeder: M62 chưa có định mức sản phẩm.');
            return;
        }

        [$start, $end] = app(ShiftTimeService::class)->range($shift, now()->toDateString());

        DB::transaction(function () use ($machine, $shift, $user, $standards, $start, $end): void {
            $declaration = ShiftProductionDeclaration::query()->updateOrCreate(
                [
                    'machine_id' => $machine->id,
                    'production_shift_id' => $shift->id,
                    'production_date' => now()->toDateString(),
                ],
                [
                    'shift_started_at' => $start,
                    'shift_ended_at' => $end,
                    'good_quantity' => 195,
                    'reject_quantity' => 5,
                    'total_quantity' => 200,
                    'planned_downtime_minutes' => 30,
                    'planned_production_minutes' => 450,
                    'plc_runtime_minutes' => null,
                    'unplanned_downtime_minutes' => null,
                    'availability' => null,
                    'performance' => null,
                    'quality_rate' => 0.975,
                    'oee' => null,
                    'status' => 'draft',
                    'operator_id' => $user->id,
                    'submitted_at' => null,
                    'note' => 'Phiếu dữ liệu mẫu cho chức năng kiểm nhập sản lượng nhiều sản phẩm.',
                ]
            );

            $declaration->items()->delete();

            foreach ($standards as $index => $standard) {
                $product = $standard->product;
                $total = $index === 0 ? 120 : 80;
                $good = $index === 0 ? 118 : 77;
                $reject = $index === 0 ? 1 : 2;
                $scrap = $index === 0 ? 1 : 1;
                $length = (float) ($product->default_length_m ?: 4);
                $weight = (float) ($product->standard_weight_g ?: 0);
                $factor = $length * $weight / 1000;

                $declaration->items()->create([
                    'product_id' => $product->id,
                    'machine_product_standard_id' => $standard->id,
                    'product_code_snapshot' => $product->material_code,
                    'product_name_snapshot' => $product->material_name,
                    'unit_snapshot' => $product->unit,
                    'standard_weight_g_snapshot' => $product->standard_weight_g,
                    'default_length_m_snapshot' => $product->default_length_m,
                    'actual_length_m' => $length,
                    'standard_rate_snapshot' => $standard->standard_rate,
                    'rate_unit_snapshot' => $standard->rate_unit,
                    'total_quantity' => $total,
                    'good_quantity' => $good,
                    'reject_quantity' => $reject,
                    'scrap_quantity' => $scrap,
                    'converted_total_kg' => round($total * $factor, 3),
                    'converted_good_kg' => round($good * $factor, 3),
                    'converted_reject_kg' => round($reject * $factor, 3),
                    'converted_scrap_kg' => round($scrap * $factor, 3),
                    'work_order' => 'LSX-TEST-' . ($index + 1),
                    'display_order' => $index,
                ]);
            }
        });
    }
}
