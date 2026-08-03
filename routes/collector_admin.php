<?php

use App\Http\Controllers\PlcCollectorTokenController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post(
        '/plcs/{plc}/collector-token',
        [PlcCollectorTokenController::class, 'regenerate']
    )
        ->middleware('permission:plcs.update')
        ->name('plcs.collector-token');
});
