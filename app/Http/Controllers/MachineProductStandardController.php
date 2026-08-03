<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMachineProductStandardRequest;
use App\Http\Requests\UpdateMachineProductStandardRequest;
use App\Models\Machine;
use App\Models\MachineProductStandard;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MachineProductStandardController extends Controller
{
    public function index(Request $request): View
    {
        $filters=$request->validate(['keyword'=>['nullable','string','max:255'],'machine_id'=>['nullable','integer','exists:machines,id'],'status'=>['nullable','in:active,inactive']]);
        $standards=MachineProductStandard::query()->with(['machine','product'])
            ->when($filters['keyword']??null,fn($q,$k)=>$q->whereHas('product',fn($p)=>$p->where('material_code','like',"%{$k}%")->orWhere('material_name','like',"%{$k}%")))
            ->when($filters['machine_id']??null,fn($q,$id)=>$q->where('machine_id',$id))
            ->when(($filters['status']??null)==='active',fn($q)=>$q->where('is_active',true))
            ->when(($filters['status']??null)==='inactive',fn($q)=>$q->where('is_active',false))
            ->orderBy('machine_id')->orderBy('product_id')->paginate(20)->withQueryString();
        $machines=Machine::query()->orderBy('code')->get();
        return view('machine-product-standards.index',compact('standards','machines','filters'));
    }
    public function create(): View { return view('machine-product-standards.create',['machines'=>Machine::where('is_active',true)->orderBy('code')->get(),'products'=>Product::where('is_active',true)->orderBy('material_code')->get()]); }
    public function store(StoreMachineProductStandardRequest $request): RedirectResponse { MachineProductStandard::create($request->validated()); return redirect()->route('machine-product-standards.index')->with('success','Đã tạo định mức máy - sản phẩm.'); }
    public function edit(MachineProductStandard $machineProductStandard): View { return view('machine-product-standards.edit',['standard'=>$machineProductStandard,'machines'=>Machine::orderBy('code')->get(),'products'=>Product::orderBy('material_code')->get()]); }
    public function update(UpdateMachineProductStandardRequest $request, MachineProductStandard $machineProductStandard): RedirectResponse { $machineProductStandard->update($request->validated()); return redirect()->route('machine-product-standards.index')->with('success','Đã cập nhật định mức.'); }
    public function destroy(MachineProductStandard $machineProductStandard): RedirectResponse { $machineProductStandard->delete(); return back()->with('success','Đã xóa định mức.'); }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['import_file'=>['required','file','mimes:xlsx,xls','max:10240']]);
        $rows=IOFactory::load($request->file('import_file')->getRealPath())->getActiveSheet()->toArray(null,true,true,false);
        if(count($rows)<2) return back()->withErrors(['import_file'=>'File Excel không có dữ liệu.']);
        $header=array_map(fn($v)=>$this->header((string)$v),array_shift($rows));
        $pos=$this->positions($header,['machine_code'=>['ma_may','mã máy','machine_code'],'material_code'=>['ma_vat_tu','mã vật tư','material_code'],'standard_rate'=>['nang_suat_dinh_muc','năng suất định mức','standard_rate'],'rate_unit'=>['don_vi_nang_suat','đơn vị năng suất','rate_unit'],'effective_from'=>['tu_ngay','từ ngày','effective_from'],'effective_to'=>['den_ngay','đến ngày','effective_to'],'is_active'=>['trang_thai','trạng thái','is_active'],'notes'=>['ghi_chu','ghi chú','notes']]);
        foreach(['machine_code','material_code','standard_rate'] as $f) if(!isset($pos[$f])) return back()->withErrors(['import_file'=>'Thiếu cột bắt buộc: Mã máy, Mã vật tư hoặc Năng suất định mức.']);
        $created=$updated=$skipped=0;
        foreach($rows as $row){
            $get=fn($f)=>isset($pos[$f])?trim((string)($row[$pos[$f]]??'')):'';
            $machine=Machine::where('code',mb_strtoupper($get('machine_code')))->first();
            $product=Product::withTrashed()->where('material_code',mb_strtoupper($get('material_code')))->first();
            $rate=$this->number($get('standard_rate'));
            if(!$machine||!$product||!$rate||$rate<=0){$skipped++;continue;}
            $payload=['standard_rate'=>$rate,'rate_unit'=>$get('rate_unit')?:'kg/h','effective_from'=>$this->excelDate($row[$pos['effective_from']]??null),'effective_to'=>$this->excelDate($row[$pos['effective_to']]??null),'is_active'=>$this->active($get('is_active')),'notes'=>$get('notes')?:null];
            $model=MachineProductStandard::withTrashed()->where('machine_id',$machine->id)->where('product_id',$product->id)->first();
            if($model){$model->restore();$model->update($payload);$updated++;}else{MachineProductStandard::create($payload+['machine_id'=>$machine->id,'product_id'=>$product->id]);$created++;}
        }
        return back()->with('success',"Nhập Excel hoàn tất: thêm {$created}, cập nhật {$updated}, bỏ qua {$skipped} dòng.");
    }

    public function export(): StreamedResponse
    {
        return response()->streamDownload(function(){
            $s=new Spreadsheet();$sh=$s->getActiveSheet();$sh->setTitle('Dinh muc may-san pham');
            $sh->fromArray(['Mã máy','Tên máy','Mã vật tư','Tên vật tư','Năng suất định mức','Đơn vị năng suất','Từ ngày','Đến ngày','Trạng thái','Ghi chú'],null,'A1');
            $r=2; foreach(MachineProductStandard::with(['machine','product'])->orderBy('machine_id')->get() as $x){$sh->fromArray([$x->machine->code,$x->machine->name,$x->product->material_code,$x->product->material_name,(float)$x->standard_rate,$x->rate_unit,optional($x->effective_from)->format('d/m/Y'),optional($x->effective_to)->format('d/m/Y'),$x->is_active?'Hoạt động':'Ngừng',$x->notes],null,"A{$r}");$r++;}
            $sh->getStyle('A1:J1')->getFont()->setBold(true); foreach(range('A','J') as $c)$sh->getColumnDimension($c)->setAutoSize(true); (new Xlsx($s))->save('php://output');
        },'dinh-muc-may-san-pham-'.now()->format('Ymd-His').'.xlsx',['Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function header(string $v): string { return mb_strtolower(trim($v)); }
    private function positions(array $h,array $aliases): array { $p=[];foreach($aliases as $f=>$names)foreach($names as $n){$i=array_search($this->header($n),$h,true);if($i!==false){$p[$f]=$i;break;}}return $p; }
    private function number(string $v): ?float { if($v==='')return null;$v=str_replace([' ','\xc2\xa0'],'',$v);if(str_contains($v,',')&&!str_contains($v,'.'))$v=str_replace(',','.',$v);elseif(str_contains($v,',')&&str_contains($v,'.'))$v=str_replace(['.',','],['','.'],$v);return is_numeric($v)?(float)$v:null; }
    private function active(string $v): bool { return !in_array(mb_strtolower(trim($v)),['0','false','inactive','ngừng','không hoạt động'],true); }
    private function excelDate(mixed $v): ?string { if($v===null||$v==='')return null;if(is_numeric($v))return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$v)->format('Y-m-d');foreach(['d/m/Y','Y-m-d'] as $f){$d=\DateTime::createFromFormat($f,trim((string)$v));if($d)return $d->format('Y-m-d');}return null; }
}
