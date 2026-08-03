<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlcRequest;
use App\Http\Requests\UpdatePlcRequest;
use App\Models\Machine;
use App\Models\Plc;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PlcController extends Controller
{
    public function index(): View
    {
        $plcs = Plc::query()
            ->with('machine.productionLine.plant')->withCount('tags')->orderBy('name')->paginate(20);
        return view('plcs.index', compact('plcs'));
    }

    public function create(): View
    {
        $machines = Machine::query()->with('productionLine.plant')->where('is_active', true)->orderBy('code')->get();
        return view('plcs.create', compact('machines'));
    }

    public function store(StorePlcRequest $request): RedirectResponse
    {
        Plc::query()->create($request->validated());
        return redirect()->route('plcs.index')->with('success', 'Đã tạo PLC.');
    }

    public function edit(Plc $plc): View
    {
        $machines = Machine::query()->with('productionLine.plant')->orderBy('code')->get();
        return view('plcs.edit', compact('plc', 'machines'));
    }

    public function update(UpdatePlcRequest $request, Plc $plc): RedirectResponse
    {
        $plc->update($request->validated());
        return redirect()->route('plcs.index')->with('success', 'Đã cập nhật PLC.');
    }

    public function destroy(Plc $plc): RedirectResponse
    {
        abort_if($plc->tags()->exists(), 422, 'PLC đang có tag.');
        $plc->delete();
        return back()->with('success', 'Đã xóa PLC.');
    }
}
