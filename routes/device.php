<?php

use App\Http\Controllers\MachineController;
use App\Http\Controllers\PlantController;
use App\Http\Controllers\PlcController;
use App\Http\Controllers\PlcTagController;
use App\Http\Controllers\ProductionLineController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/plants', [PlantController::class, 'index'])
        ->middleware('permission:plants.view')
        ->name('plants.index');
    Route::get('/plants/create', [PlantController::class, 'create'])
        ->middleware('permission:plants.create')
        ->name('plants.create');
    Route::post('/plants', [PlantController::class, 'store'])
        ->middleware('permission:plants.create')
        ->name('plants.store');
    Route::get('/plants/{plant}/edit', [PlantController::class, 'edit'])
        ->middleware('permission:plants.update')
        ->name('plants.edit');
    Route::match(['put', 'patch'], '/plants/{plant}', [PlantController::class, 'update'])
        ->middleware('permission:plants.update')
        ->name('plants.update');
    Route::delete('/plants/{plant}', [PlantController::class, 'destroy'])
        ->middleware('permission:plants.delete')
        ->name('plants.destroy');

    Route::get('/production-lines', [ProductionLineController::class, 'index'])
        ->middleware('permission:production-lines.view')
        ->name('production-lines.index');
    Route::get('/production-lines/create', [ProductionLineController::class, 'create'])
        ->middleware('permission:production-lines.create')
        ->name('production-lines.create');
    Route::post('/production-lines', [ProductionLineController::class, 'store'])
        ->middleware('permission:production-lines.create')
        ->name('production-lines.store');
    Route::get('/production-lines/{productionLine}/edit', [ProductionLineController::class, 'edit'])
        ->middleware('permission:production-lines.update')
        ->name('production-lines.edit');
    Route::match(['put', 'patch'], '/production-lines/{productionLine}', [ProductionLineController::class, 'update'])
        ->middleware('permission:production-lines.update')
        ->name('production-lines.update');
    Route::delete('/production-lines/{productionLine}', [ProductionLineController::class, 'destroy'])
        ->middleware('permission:production-lines.delete')
        ->name('production-lines.destroy');

    Route::get('/machines', [MachineController::class, 'index'])
        ->middleware('permission:machines.view')
        ->name('machines.index');
    Route::get('/machines/create', [MachineController::class, 'create'])
        ->middleware('permission:machines.create')
        ->name('machines.create');
    Route::post('/machines', [MachineController::class, 'store'])
        ->middleware('permission:machines.create')
        ->name('machines.store');
    Route::get('/machines/{machine}/edit', [MachineController::class, 'edit'])
        ->middleware('permission:machines.update')
        ->name('machines.edit');
    Route::match(['put', 'patch'], '/machines/{machine}', [MachineController::class, 'update'])
        ->middleware('permission:machines.update')
        ->name('machines.update');
    Route::delete('/machines/{machine}', [MachineController::class, 'destroy'])
        ->middleware('permission:machines.delete')
        ->name('machines.destroy');

    Route::get('/plcs', [PlcController::class, 'index'])
        ->middleware('permission:plcs.view')
        ->name('plcs.index');
    Route::get('/plcs/create', [PlcController::class, 'create'])
        ->middleware('permission:plcs.create')
        ->name('plcs.create');
    Route::post('/plcs', [PlcController::class, 'store'])
        ->middleware('permission:plcs.create')
        ->name('plcs.store');
    Route::get('/plcs/{plc}/edit', [PlcController::class, 'edit'])
        ->middleware('permission:plcs.update')
        ->name('plcs.edit');
    Route::match(['put', 'patch'], '/plcs/{plc}', [PlcController::class, 'update'])
        ->middleware('permission:plcs.update')
        ->name('plcs.update');
    Route::delete('/plcs/{plc}', [PlcController::class, 'destroy'])
        ->middleware('permission:plcs.delete')
        ->name('plcs.destroy');

    Route::get('/plc-tags', [PlcTagController::class, 'index'])
        ->middleware('permission:plc-tags.view')
        ->name('plc-tags.index');
    Route::get('/plc-tags/create', [PlcTagController::class, 'create'])
        ->middleware('permission:plc-tags.create')
        ->name('plc-tags.create');
    Route::post('/plc-tags', [PlcTagController::class, 'store'])
        ->middleware('permission:plc-tags.create')
        ->name('plc-tags.store');
    Route::get('/plc-tags/{plcTag}/edit', [PlcTagController::class, 'edit'])
        ->middleware('permission:plc-tags.update')
        ->name('plc-tags.edit');
    Route::match(['put', 'patch'], '/plc-tags/{plcTag}', [PlcTagController::class, 'update'])
        ->middleware('permission:plc-tags.update')
        ->name('plc-tags.update');
    Route::delete('/plc-tags/{plcTag}', [PlcTagController::class, 'destroy'])
        ->middleware('permission:plc-tags.delete')
        ->name('plc-tags.destroy');
});
