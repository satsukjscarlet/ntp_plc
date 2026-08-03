<?php

namespace Database\Seeders;

use App\Models\DowntimeReason;
use App\Models\ProductionShift;
use Illuminate\Database\Seeder;

class OeeFoundationSeeder extends Seeder
{
    public function run(): void
    {
        // Đây là cấu hình mặc định; có thể sửa trực tiếp trong DB nếu
        // giờ ca thực tế của nhà máy khác.
        $shifts = [
            ['code' => 'CA_1', 'name' => 'Ca 1', 'start_time' => '06:00:00', 'end_time' => '14:00:00', 'display_order' => 1],
            ['code' => 'CA_2', 'name' => 'Ca 2', 'start_time' => '14:00:00', 'end_time' => '22:00:00', 'display_order' => 2],
            ['code' => 'CA_3', 'name' => 'Ca 3', 'start_time' => '22:00:00', 'end_time' => '06:00:00', 'display_order' => 3],
        ];

        foreach ($shifts as $shift) {
            ProductionShift::query()->updateOrCreate(
                ['code' => $shift['code']],
                $shift + ['is_active' => true]
            );
        }

        $reasons = [
            ['code' => 'CHANGE_DIE', 'name' => 'Thay khuôn / đổi cỡ', 'category' => 'SETUP', 'is_planned' => true],
            ['code' => 'WAIT_MATERIAL', 'name' => 'Chờ nguyên liệu', 'category' => 'MATERIAL', 'is_planned' => false],
            ['code' => 'MACHINE_FAILURE', 'name' => 'Sự cố máy', 'category' => 'BREAKDOWN', 'is_planned' => false],
            ['code' => 'POWER_FAILURE', 'name' => 'Mất điện / điện không ổn định', 'category' => 'UTILITY', 'is_planned' => false],
            ['code' => 'WAIT_QC', 'name' => 'Chờ kiểm tra chất lượng', 'category' => 'QUALITY', 'is_planned' => false],
            ['code' => 'MAINTENANCE', 'name' => 'Bảo trì theo kế hoạch', 'category' => 'MAINTENANCE', 'is_planned' => true],
            ['code' => 'MEAL_BREAK', 'name' => 'Nghỉ ăn giữa ca', 'category' => 'PLANNED', 'is_planned' => true],
            ['code' => 'OTHER', 'name' => 'Nguyên nhân khác', 'category' => 'OTHER', 'is_planned' => false],
        ];

        foreach ($reasons as $index => $reason) {
            DowntimeReason::query()->updateOrCreate(
                ['code' => $reason['code']],
                $reason + [
                    'display_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
