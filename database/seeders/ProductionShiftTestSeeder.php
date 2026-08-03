<?php
namespace Database\Seeders;
use App\Models\ProductionShift;
use Illuminate\Database\Seeder;
class ProductionShiftTestSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code'=>'CA1','name'=>'Ca 1','start_time'=>'06:00:00','end_time'=>'14:00:00','display_order'=>10,'is_active'=>true],
            ['code'=>'CA2','name'=>'Ca 2','start_time'=>'14:00:00','end_time'=>'22:00:00','display_order'=>20,'is_active'=>true],
            ['code'=>'CA3','name'=>'Ca 3','start_time'=>'22:00:00','end_time'=>'06:00:00','display_order'=>30,'is_active'=>true],
        ] as $item) ProductionShift::updateOrCreate(['code'=>$item['code']],$item);
    }
}
