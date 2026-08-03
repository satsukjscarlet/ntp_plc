<?php

namespace Database\Seeders;

use App\Models\DowntimeReason;
use Illuminate\Database\Seeder;

class DowntimeReasonSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['code'=>'PM','name'=>'Bảo trì định kỳ','category'=>'MAINTENANCE','is_planned'=>true,'affects_availability'=>false,'requires_note'=>false,'display_order'=>10],
            ['CHANGEOVER','Chuyển đổi sản phẩm','CHANGEOVER',true,false,false,20],
            ['CLEANING','Vệ sinh máy và khu vực','CLEANING',true,false,false,30],
            ['MECHANICAL','Sự cố cơ khí','EQUIPMENT',false,true,true,40],
            ['ELECTRICAL','Sự cố điện','EQUIPMENT',false,true,true,50],
            ['NO_MATERIAL','Thiếu nguyên liệu','MATERIAL',false,true,false,60],
            ['MATERIAL_NG','Nguyên liệu không đạt','MATERIAL',false,true,true,70],
            ['NO_OPERATOR','Thiếu nhân lực','MANPOWER',false,true,true,80],
            ['WAIT_QC','Chờ kiểm tra chất lượng','QUALITY',false,true,false,90],
            ['POWER_OUTAGE','Mất điện','UTILITY',false,true,false,100],
            ['AIR_WATER','Mất khí nén/nước làm mát','UTILITY',false,true,true,110],
            ['OPERATION','Lỗi thao tác vận hành','OPERATION',false,true,true,120],
            ['NO_PLAN','Không có kế hoạch sản xuất','OTHER',true,false,false,130],
            ['OTHER','Nguyên nhân khác','OTHER',false,true,true,999],
        ];
        foreach ($items as $item) {
            if (array_is_list($item)) $item = ['code'=>$item[0],'name'=>$item[1],'category'=>$item[2],'is_planned'=>$item[3],'affects_availability'=>$item[4],'requires_note'=>$item[5],'display_order'=>$item[6]];
            DowntimeReason::withTrashed()->updateOrCreate(['code'=>$item['code']], $item + ['is_active'=>true,'display_color'=>null,'description'=>null,'deleted_at'=>null]);
        }
    }
}
