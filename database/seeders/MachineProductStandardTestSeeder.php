<?php
namespace Database\Seeders;
use App\Models\Machine;
use App\Models\MachineProductStandard;
use App\Models\Product;
use Illuminate\Database\Seeder;
class MachineProductStandardTestSeeder extends Seeder
{
    public function run(): void
    {
        $machine = Machine::where('code','M62')->first();
        if (!$machine) { $this->command?->warn('Không tìm thấy máy M62; bỏ qua MachineProductStandardTestSeeder.'); return; }
        $rates = ['PVC-D20-4M'=>520,'PVC-D25-4M'=>490,'PVC-D32-4M'=>450,'PVC-D40-4M'=>410,'HDPE-D20-100M'=>360];
        foreach ($rates as $code=>$rate) {
            $product=Product::where('material_code',$code)->first(); if (!$product) continue;
            MachineProductStandard::withTrashed()->updateOrCreate(['machine_id'=>$machine->id,'product_id'=>$product->id],['standard_rate'=>$rate,'rate_unit'=>'kg/h','effective_from'=>now()->startOfYear()->toDateString(),'effective_to'=>null,'is_active'=>true,'notes'=>'Định mức test cho M62','deleted_at'=>null]);
        }
    }
}
