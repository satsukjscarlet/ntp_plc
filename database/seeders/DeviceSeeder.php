<?php

namespace Database\Seeders;

use App\Models\Machine;
use App\Models\Plant;
use App\Models\Plc;
use App\Models\PlcTag;
use App\Models\ProductionLine;
use Illuminate\Database\Seeder;
use RuntimeException;

class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        $plant = Plant::query()->updateOrCreate(
            [
                'code' => 'NTP',
            ],
            [
                'name' => 'Nhựa Tiền Phong',
                'address' => null,
                'description' => null,
                'is_active' => true,
            ]
        );

        $productionLine = ProductionLine::query()->updateOrCreate(
            [
                'plant_id' => $plant->id,
                'code' => 'EP',
            ],
            [
                'name' => 'Ép nhựa',
                'description' => null,
                'is_active' => true,
            ]
        );

        $machine = Machine::query()->updateOrCreate(
            [
                'production_line_id' => $productionLine->id,
                'code' => 'M62',
            ],
            [
                'name' => 'AMUT',
                'model' => null,
                'manufacturer' => 'AMUT',
                'serial_number' => null,
                'installation_date' => null,
                'description' => null,
                'is_active' => true,
            ]
        );

        $plainToken = trim((string) config(
            'plc.collector_tokens.M62'
        ));

        if ($plainToken === '') {
            throw new RuntimeException(
                'PLC_COLLECTOR_TOKEN_M62 chưa được cấu hình.'
            );
        }

        $plc = Plc::query()->updateOrCreate(
            [
                'machine_id' => $machine->id,
                'ip_address' => '10.65.100.105',
            ],
            [
                'name' => 'PLC M62',
                'brand' => 'Siemens',
                'model' => null,
                'port' => 80,
                'protocol' => 'HTTP_PORTAL',
                'collector_url' =>
                    'http://10.65.100.105/Portal/Portal.mwsl?PriNav=Vartables',
                'scan_interval' => 5,
                'timezone' => 'Asia/Ho_Chi_Minh',
                'collector_token_hash' => hash(
                    'sha256',
                    $plainToken
                ),
                'connection_status' => 'UNKNOWN',
                'description' => null,
                'is_active' => true,
            ]
        );

        $tags = [
            [
                'address' => '%MD264',
                'source_name' => 'Machine capacity',
                'display_name' => 'Năng suất máy',
                'data_type' => 'FLOAT',
                'unit' => 'kg/h',
                'display_group' => 'production',
                'display_order' => 10,
                'oee_role' => 'MACHINE_CAPACITY',
            ],
            [
                'address' => '%MD1100',
                'source_name' => 'Actual temperature neck',
                'display_name' => 'Nhiệt độ thực tế cổ CL',
                'data_type' => 'FLOAT',
                'unit' => '°C',
                'display_group' => 'temperature',
                'display_order' => 20,
                'oee_role' => 'TEMPERATURE',
            ],
            [
                'address' => '%MD1104',
                'source_name' => 'Actual temperature XL 1',
                'display_name' => 'Nhiệt độ thực tế XL 1',
                'data_type' => 'FLOAT',
                'unit' => '°C',
                'display_group' => 'temperature',
                'display_order' => 30,
                'oee_role' => 'TEMPERATURE',
            ],
        ];

        foreach ($tags as $tag) {
            PlcTag::query()->updateOrCreate(
                [
                    'plc_id' => $plc->id,
                    'address' => $tag['address'],
                ],
                array_merge($tag, [
                    'plc_id' => $plc->id,
                    'is_visible' => true,
                    'is_active' => true,
                ])
            );
        }
    }
}