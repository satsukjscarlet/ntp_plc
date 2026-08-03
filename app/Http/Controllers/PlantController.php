<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlantRequest;
use App\Http\Requests\UpdatePlantRequest;
use App\Models\Plant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PlantController extends Controller
{
    public function index(): View
    {
        $plants = Plant::query()->withCount('productionLines')->orderBy('code')->paginate(20);
        return view('plants.index', compact('plants'));
    }

    public function create(): View
    {
        return view('plants.create');
    }

    public function store(StorePlantRequest $request): RedirectResponse
    {
        Plant::query()->create($request->validated());
        return redirect()->route('plants.index')->with('success', 'Đã tạo nhà máy.');
    }

    public function edit(Plant $plant): View
    {
        return view('plants.edit', compact('plant'));
    }

    public function update(UpdatePlantRequest $request, Plant $plant): RedirectResponse
    {
        $plant->update($request->validated());
        return redirect()->route('plants.index')->with('success', 'Đã cập nhật nhà máy.');
    }

    public function destroy(Plant $plant): RedirectResponse
    {
        abort_if($plant->productionLines()->exists(), 422, 'Nhà máy đang có dây chuyền.');
        $plant->delete();
        return back()->with('success', 'Đã xóa nhà máy.');
    }
}
