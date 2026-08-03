<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMachineRequest;
use App\Http\Requests\UpdateMachineRequest;
use App\Models\Machine;
use App\Models\ProductionLine;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class MachineController extends Controller
{
    public function index(): View
    {
        $machines = Machine::query()
            ->with('productionLine.plant')->withCount('plcs')->orderBy('code')->paginate(20);
        return view('machines.index', compact('machines'));
    }

    public function create(): View
    {
        $productionLines = ProductionLine::query()->with('plant')->where('is_active', true)->orderBy('name')->get();
        return view('machines.create', compact('productionLines'));
    }

    public function store(StoreMachineRequest $request): RedirectResponse
    {
        Machine::query()->create($request->validated());
        return redirect()->route('machines.index')->with('success', 'Đã tạo máy.');
    }

    public function edit(Machine $machine): View
    {
        $productionLines = ProductionLine::query()->with('plant')->orderBy('name')->get();
        return view('machines.edit', compact('machine', 'productionLines'));
    }

    public function update(UpdateMachineRequest $request, Machine $machine): RedirectResponse
    {
        $machine->update($request->validated());
        return redirect()->route('machines.index')->with('success', 'Đã cập nhật máy.');
    }

    public function destroy(Machine $machine): RedirectResponse
    {
        abort_if($machine->plcs()->exists(), 422, 'Máy đang có PLC.');
        $machine->delete();
        return back()->with('success', 'Đã xóa máy.');
    }
}
