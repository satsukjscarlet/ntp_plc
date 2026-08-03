<?php

use App\Http\Controllers\MonitoringLayoutEditorController;
use App\Http\Controllers\TechnologyMonitoringController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get(
        '/machines/{machineCode}/monitoring-layout',
        [MonitoringLayoutEditorController::class, 'index']
    )->name('monitoring-layout.index');

    Route::put(
        '/machines/{machineCode}/monitoring-layout/tags/{tag}',
        [MonitoringLayoutEditorController::class, 'updateTag']
    )->name('monitoring-layout.tags.update');

    Route::get(
        '/monitoring/{machineCode}/technology',
        [TechnologyMonitoringController::class, 'index']
    )->name('monitoring.technology');

    Route::get(
        '/monitoring/{machineCode}/technology/data',
        [TechnologyMonitoringController::class, 'data']
    )->name('monitoring.technology.data');
});
