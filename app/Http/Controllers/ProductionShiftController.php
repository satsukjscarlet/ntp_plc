<?php
namespace App\Http\Controllers;
use App\Models\ProductionShift;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
class ProductionShiftController extends Controller
{
 public function index(): View { return view('production-shifts.index',['shifts'=>ProductionShift::orderBy('display_order')->get()]); }
 public function create(): View { return view('production-shifts.create'); }
 public function store(Request $r): RedirectResponse { ProductionShift::create($this->data($r)); return redirect()->route('production-shifts.index')->with('success','Đã tạo ca làm việc.'); }
 public function edit(ProductionShift $productionShift): View { return view('production-shifts.edit',['shift'=>$productionShift]); }
 public function update(Request $r,ProductionShift $productionShift): RedirectResponse { $productionShift->update($this->data($r,$productionShift->id)); return redirect()->route('production-shifts.index')->with('success','Đã cập nhật ca làm việc.'); }
 public function destroy(ProductionShift $productionShift): RedirectResponse { if($productionShift->declarations()->exists()) return back()->withErrors(['shift'=>'Ca đã có dữ liệu sản xuất, không thể xóa.']); $productionShift->delete(); return back()->with('success','Đã xóa ca làm việc.'); }
 private function data(Request $r,?int $id=null): array { $d=$r->validate(['code'=>['required','string','max:30','unique:production_shifts,code,'.$id],'name'=>['required','string','max:100'],'start_time'=>['required','date_format:H:i'],'end_time'=>['required','date_format:H:i'],'display_order'=>['required','integer','min:0'],'is_active'=>['nullable','boolean']]);$d['code']=mb_strtoupper($d['code']);$d['is_active']=$r->boolean('is_active');return $d; }
}
