<?php

namespace Database\Seeders;

use App\Models\Machine;
use App\Models\MonitoringLayoutSection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class M62MonitoringLayoutSeeder extends Seeder
{
    public function run(): void
    {
        $machine = Machine::query()
            ->where('code', 'M62')
            ->firstOrFail();

        DB::transaction(function () use ($machine): void {
            $sections = [];

            foreach ([
                ['temperature', 'Thông số nhiệt', 10],
                ['operation', 'Thông số vận hành', 20],
                ['system', 'Trạng thái hệ thống', 30],
                ['production', 'Thông tin sản xuất', 40],
                ['extra', 'Thông số bổ sung', 50],
            ] as [$code, $name, $order]) {
                $sections[$code] = MonitoringLayoutSection::query()
                    ->updateOrCreate(
                        [
                            'machine_id' => $machine->id,
                            'code' => $code,
                        ],
                        [
                            'name_vi' => $name,
                            'display_order' => $order,
                            'is_active' => true,
                        ]
                    );
            }

            $temperature = $sections['temperature'];

            foreach ([
                ['set', 'Nhiệt độ đặt', 10],
                ['actual', 'Nhiệt độ thực tế', 20],
            ] as [$code, $name, $order]) {
                $temperature->rows()->updateOrCreate(
                    ['code' => $code],
                    [
                        'name_vi' => $name,
                        'display_order' => $order,
                        'is_active' => true,
                    ]
                );
            }

            $temperatureColumns = [
                'CL',
                'XL1', 'XL2', 'XL3', 'XL4', 'XL5',
                'CN',
                'XLMC1', 'XLMC2', 'XLMC3', 'XLMC4',
            ];

            for ($number = 1; $number <= 30; $number++) {
                $temperatureColumns[] = 'DH' . $number;
            }

            foreach ($temperatureColumns as $index => $name) {
                $temperature->columns()->updateOrCreate(
                    ['code' => strtolower($name)],
                    [
                        'name_vi' => $name,
                        'display_order' => ($index + 1) * 10,
                        'is_active' => true,
                    ]
                );
            }

            foreach (['operation', 'system', 'production', 'extra'] as $sectionCode) {
                $section = $sections[$sectionCode];

                for ($number = 1; $number <= 20; $number++) {
                    $section->rows()->updateOrCreate(
                        ['code' => 'hang_' . $number],
                        [
                            'name_vi' => 'Hàng ' . $number,
                            'display_order' => $number * 10,
                            'is_active' => true,
                        ]
                    );
                }

                for ($number = 1; $number <= 4; $number++) {
                    $section->columns()->updateOrCreate(
                        ['code' => 'cot_' . $number],
                        [
                            'name_vi' => 'Cột ' . $number,
                            'display_order' => $number * 10,
                            'is_active' => true,
                        ]
                    );
                }
            }
        });
    }
}
