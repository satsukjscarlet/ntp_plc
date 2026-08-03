<?php

use App\Http\Controllers\Api\PlcDataController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Collector\SyncPlcTagsController;

Route::middleware('collector.auth')
    ->prefix('collector')
    ->group(function (): void {
        Route::post('/sync-tags', SyncPlcTagsController::class);
    });


Route::prefix('collector')
    ->middleware('collector.auth')
    ->group(function (): void {
        Route::post('/plc-data', [
            PlcDataController::class,
            'store',
        ])->name('api.collector.plc-data');

        Route::post('/heartbeat', [
            PlcDataController::class,
            'heartbeat',
        ])->name('api.collector.heartbeat');
    });

use App\Http\Controllers\Api\MonitoringApiController;

Route::get(
    '/monitoring/{machineCode}',
    [MonitoringApiController::class, 'show']
)->name('api.monitoring.show');