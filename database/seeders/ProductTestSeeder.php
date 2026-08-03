<?php
namespace Database\Seeders;
use App\Models\Product;
use Illuminate\Database\Seeder;
class ProductTestSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['material_code'=>'PVC-D20-4M','material_name'=>'Ống uPVC D20 dài 4m','unit'=>'m','standard_weight_g'=>125.0000,'default_length_m'=>4.0000],
            ['material_code'=>'PVC-D25-4M','material_name'=>'Ống uPVC D25 dài 4m','unit'=>'m','standard_weight_g'=>172.0000,'default_length_m'=>4.0000],
            ['material_code'=>'PVC-D32-4M','material_name'=>'Ống uPVC D32 dài 4m','unit'=>'m','standard_weight_g'=>238.0000,'default_length_m'=>4.0000],
            ['material_code'=>'PVC-D40-4M','material_name'=>'Ống uPVC D40 dài 4m','unit'=>'m','standard_weight_g'=>315.0000,'default_length_m'=>4.0000],
            ['material_code'=>'HDPE-D20-100M','material_name'=>'Ống HDPE D20 cuộn 100m','unit'=>'m','standard_weight_g'=>118.0000,'default_length_m'=>100.0000],
        ];
        foreach ($items as $item) Product::withTrashed()->updateOrCreate(['material_code'=>$item['material_code']],$item+['is_active'=>true,'notes'=>'Dữ liệu test V5.5']);
    }
}
