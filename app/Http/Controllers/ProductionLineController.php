<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductionLineRequest;
use App\Http\Requests\UpdateProductionLineRequest;
use App\Models\Plant;
use App\Models\ProductionLine;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ProductionLineController extends Controller
{
    public function index(): View
    {
        $productionLines = ProductionLine::query()
            ->with('plant')->withCount('machines')->orderBy('code')->paginate(20);
        return view('production_lines.index', compact('productionLines'));
    }

    public function create(): View
    {
        $plants = Plant::query()->where('is_active', true)->orderBy('name')->get();
        return view('production_lines.create', compact('plants'));
    }

    public function store(StoreProductionLineRequest $request): RedirectResponse
    {
        ProductionLine::query()->create($request->validated());
        return redirect()->route('production-lines.index')->with('success', 'Đã tạo dây chuyền.');
    }

    public function edit(ProductionLine $productionLine): View
    {
        $plants = Plant::query()->orderBy('name')->get();
        return view('production_lines.edit', compact('productionLine', 'plants'));
    }

    public function update(UpdateProductionLineRequest $request, ProductionLine $productionLine): RedirectResponse
    {
        $productionLine->update($request->validated());
        return redirect()->route('production-lines.index')->with('success', 'Đã cập nhật dây chuyền.');
    }

    public function destroy(ProductionLine $productionLine): RedirectResponse
    {
        abort_if($productionLine->machines()->exists(), 422, 'Dây chuyền đang có máy.');
        $productionLine->delete();
        return back()->with('success', 'Đã xóa dây chuyền.');
    }
}
