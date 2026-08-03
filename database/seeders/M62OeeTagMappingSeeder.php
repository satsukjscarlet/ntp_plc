<?php

namespace Database\Seeders;

use App\Models\Machine;
use App\Models\MachineOeeSetting;
use App\Models\PlcTag;
use Illuminate\Database\Seeder;

class M62OeeTagMappingSeeder extends Seeder
{
    public function run(): void
    {
        $machine = Machine::query()->where('code', 'M62')->first();

        if (!$machine) {
            $this->command?->warn('Không tìm thấy máy M62.');
            return;
        }

        $plcIds = $machine->plcs()->pluck('id');

        $md488 = PlcTag::query()
            ->whereIn('plc_id', $plcIds)
            ->where('address', '%MD488')
            ->first();

        $md264 = PlcTag::query()
            ->whereIn('plc_id', $plcIds)
            ->where('address', '%MD264')
            ->first();

        if (!$md488 || !$md264) {
            $this->command?->warn('Không tìm đủ MD488 và MD264 thuộc PLC của M62.');
            return;
        }

        MachineOeeSetting::query()->updateOrCreate(
            ['machine_id' => $machine->id],
            [
                'availability_tag_id' => $md488->id,
                'availability_value_unit' => 'hours',
                'performance_tag_id' => $md264->id,
                'performance_aggregation' => 'stable_average',
                'performance_stability_tolerance' => 1.0,
                'performance_stability_min_seconds' => 60,
                'performance_min_valid_value' => 0.0,
                'is_active' => true,
            ]
        );

        $this->command?->info(
            "Đã mapping M62: MD488 ID {$md488->id}; MD264 ID {$md264->id}."
        );
    }
}
