<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDowntimeReasonRequest;
use App\Http\Requests\UpdateDowntimeReasonRequest;
use App\Models\DowntimeReason;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DowntimeReasonController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'keyword' => ['nullable','string','max:255'],
            'category' => ['nullable','string'],
            'status' => ['nullable','in:active,inactive'],
            'planned' => ['nullable','in:planned,unplanned'],
        ]);

        $reasons = DowntimeReason::query()
            ->when($filters['keyword'] ?? null, fn ($q, $k) => $q->where(fn ($x) => $x->where('code','like',"%{$k}%")->orWhere('name','like',"%{$k}%")))
            ->when($filters['category'] ?? null, fn ($q, $v) => $q->where('category',$v))
            ->when(($filters['status'] ?? null) === 'active', fn ($q) => $q->where('is_active',true))
            ->when(($filters['status'] ?? null) === 'inactive', fn ($q) => $q->where('is_active',false))
            ->when(($filters['planned'] ?? null) === 'planned', fn ($q) => $q->where('is_planned',true))
            ->when(($filters['planned'] ?? null) === 'unplanned', fn ($q) => $q->where('is_planned',false))
            ->orderBy('display_order')->orderBy('code')->paginate(20)->withQueryString();

        return view('downtime-reasons.index', [
            'reasons' => $reasons,
            'filters' => $filters,
            'categories' => DowntimeReason::CATEGORIES,
        ]);
    }

    public function create(): View { return view('downtime-reasons.create',['categories'=>DowntimeReason::CATEGORIES]); }
    public function store(StoreDowntimeReasonRequest $request): RedirectResponse { DowntimeReason::create($request->validated()); return redirect()->route('downtime-reasons.index')->with('success','Đã tạo nguyên nhân dừng.'); }
    public function edit(DowntimeReason $downtimeReason): View { return view('downtime-reasons.edit',['reason'=>$downtimeReason,'categories'=>DowntimeReason::CATEGORIES]); }
    public function update(UpdateDowntimeReasonRequest $request, DowntimeReason $downtimeReason): RedirectResponse { $downtimeReason->update($request->validated()); return redirect()->route('downtime-reasons.index')->with('success','Đã cập nhật nguyên nhân dừng.'); }
    public function destroy(DowntimeReason $downtimeReason): RedirectResponse { $downtimeReason->delete(); return back()->with('success','Đã xóa nguyên nhân dừng.'); }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['import_file'=>['required','file','mimes:xlsx,xls','max:10240']]);
        $rows = IOFactory::load($request->file('import_file')->getRealPath())->getActiveSheet()->toArray(null,true,true,false);
        if (count($rows) < 2) return back()->withErrors(['import_file'=>'File Excel không có dữ liệu.']);
        $header = array_map(fn ($v) => $this->header((string) $v), array_shift($rows));
        $positions = $this->positions($header, [
            'code'=>['mã','ma','mã nguyên nhân','ma nguyen nhan','code'],
            'name'=>['tên nguyên nhân','ten nguyen nhan','name'],
            'category'=>['nhóm','nhom','category'],
            'is_planned'=>['dừng kế hoạch','dung ke hoach','is_planned'],
            'affects_availability'=>['tính availability','tinh availability','affects_availability'],
            'requires_note'=>['bắt buộc ghi chú','bat buoc ghi chu','requires_note'],
            'is_active'=>['trạng thái','trang thai','is_active'],
            'display_order'=>['thứ tự','thu tu','display_order'],
            'description'=>['mô tả','mo ta','description'],
        ]);
        foreach (['code','name'] as $required) if (!isset($positions[$required])) return back()->withErrors(['import_file'=>'Thiếu cột bắt buộc: Mã nguyên nhân hoặc Tên nguyên nhân.']);

        $created=$updated=$skipped=0;
        foreach ($rows as $row) {
            $get = fn ($f) => isset($positions[$f]) ? trim((string) ($row[$positions[$f]] ?? '')) : '';
            $code = mb_strtoupper($get('code')); $name = $get('name');
            if ($code === '' || $name === '') { $skipped++; continue; }
            $category = $this->category($get('category'));
            $payload = [
                'name'=>$name,
                'category'=>$category,
                'is_planned'=>$this->yes($get('is_planned')),
                'affects_availability'=>$get('affects_availability') === '' ? true : $this->yes($get('affects_availability')),
                'requires_note'=>$this->yes($get('requires_note')),
                'is_active'=>$get('is_active') === '' ? true : $this->active($get('is_active')),
                'display_order'=>(int) ($get('display_order') ?: 0),
                'description'=>$get('description') ?: null,
            ];
            $model = DowntimeReason::withTrashed()->where('code',$code)->first();
            if ($model) { $model->restore(); $model->update($payload); $updated++; }
            else { DowntimeReason::create($payload + ['code'=>$code]); $created++; }
        }
        return back()->with('success',"Nhập Excel hoàn tất: thêm {$created}, cập nhật {$updated}, bỏ qua {$skipped} dòng.");
    }

    public function export(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $s = new Spreadsheet(); $sh = $s->getActiveSheet(); $sh->setTitle('Nguyen nhan dung');
            $sh->fromArray(['Mã nguyên nhân','Tên nguyên nhân','Nhóm','Dừng kế hoạch','Tính Availability','Bắt buộc ghi chú','Trạng thái','Thứ tự','Mô tả'],null,'A1');
            $row=2;
            foreach (DowntimeReason::orderBy('display_order')->get() as $x) {
                $sh->fromArray([$x->code,$x->name,$x->category_label,$x->is_planned?'Có':'Không',$x->affects_availability?'Có':'Không',$x->requires_note?'Có':'Không',$x->is_active?'Hoạt động':'Ngừng',$x->display_order,$x->description],null,"A{$row}"); $row++;
            }
            $sh->getStyle('A1:I1')->getFont()->setBold(true); foreach (range('A','I') as $c) $sh->getColumnDimension($c)->setAutoSize(true); (new Xlsx($s))->save('php://output');
        }, 'nguyen-nhan-dung-'.now()->format('Ymd-His').'.xlsx', ['Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function header(string $v): string { return mb_strtolower(trim($v)); }
    private function positions(array $header,array $aliases): array { $p=[]; foreach ($aliases as $field=>$names) foreach ($names as $name) { $i=array_search($this->header($name),$header,true); if ($i!==false) { $p[$field]=$i; break; } } return $p; }
    private function yes(string $v): bool { return in_array(mb_strtolower(trim($v)),['1','true','yes','y','có','co','x'],true); }
    private function active(string $v): bool { return !in_array(mb_strtolower(trim($v)),['0','false','inactive','ngừng','không hoạt động','khong hoat dong'],true); }
    private function category(string $v): string { $v=mb_strtolower(trim($v)); foreach (DowntimeReason::CATEGORIES as $key=>$label) if ($v===mb_strtolower($key)||$v===mb_strtolower($label)) return $key; return 'OTHER'; }
}
