<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlcTagRequest;
use App\Http\Requests\UpdatePlcTagRequest;
use App\Models\Plc;
use App\Models\PlcTag;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PlcTagController extends Controller
{
    public function index(): View
    {
        $plcTags = PlcTag::query()
            ->with('plc.machine')
            ->when(request('plc_id'), fn ($q, $id) => $q->where('plc_id', $id))
            ->orderBy('plc_id')->orderBy('display_order')->paginate(30)->withQueryString();

        $plcs = Plc::query()->with('machine')->orderBy('name')->get();

        return view('plc_tags.index', compact('plcTags', 'plcs'));
    }

    public function create(): View
    {
        $plcs = Plc::query()->with('machine')->where('is_active', true)->orderBy('name')->get();
        return view('plc_tags.create', compact('plcs'));
    }

    public function store(StorePlcTagRequest $request): RedirectResponse
    {
        PlcTag::query()->create($request->validated());
        return redirect()->route('plc-tags.index')->with('success', 'Đã tạo tag PLC.');
    }

    public function edit(PlcTag $plcTag): View
    {
        $plcs = Plc::query()->with('machine')->orderBy('name')->get();
        return view('plc_tags.edit', compact('plcTag', 'plcs'));
    }

    public function update(UpdatePlcTagRequest $request, PlcTag $plcTag): RedirectResponse
    {
        $plcTag->update($request->validated());
        return redirect()->route('plc-tags.index')->with('success', 'Đã cập nhật tag PLC.');
    }

    public function destroy(PlcTag $plcTag): RedirectResponse
    {
        $plcTag->delete();
        return back()->with('success', 'Đã xóa tag PLC.');
    }
}
