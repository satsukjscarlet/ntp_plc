<?php

use App\Http\Controllers\MonitoringLayoutEditorController;
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

    Route::post(
        '/machines/{machineCode}/monitoring-layout/rows',
        [MonitoringLayoutEditorController::class, 'storeRow']
    )->name('monitoring-layout.rows.store');

    Route::post(
        '/machines/{machineCode}/monitoring-layout/columns',
        [MonitoringLayoutEditorController::class, 'storeColumn']
    )->name('monitoring-layout.columns.store');
});
