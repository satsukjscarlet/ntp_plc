<?php

// COPY route này vào trong nhóm Route::middleware(['auth'])->group(...)
// và đặt TRƯỚC route /production-declarations/{productionDeclaration}.

use App\Http\Controllers\ProductionDeclarationController;
use Illuminate\Support\Facades\Route;

Route::get(
    '/production-declarations/product-options',
    [ProductionDeclarationController::class, 'productOptions']
)
    ->middleware('permission:production-declarations.create')
    ->name('production-declarations.product-options');
