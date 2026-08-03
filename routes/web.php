<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Admin\PlcTagDashboardController;
use App\Http\Controllers\Admin\PlcTagSyncController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DailyProductionReportController;
use App\Http\Controllers\DowntimeReasonController;
use App\Http\Controllers\MachineTagController;
use App\Http\Controllers\MachineOeeSettingController;
use App\Http\Controllers\ProductionDeclarationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PlcCollectorTokenController;
use App\Http\Controllers\ProductionShiftController;
use App\Http\Controllers\MachineProductStandardController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\MonitoringLayoutController;
use App\Http\Controllers\OeeReportController;
use App\Http\Controllers\MonitoringLayoutEditorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TechnologyMonitoringController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get(
    '/',
    fn() => auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login')
);

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::get(
        '/dashboard/oee/machines/{machine:code}',
        [DashboardController::class, 'machine']
    )
        ->middleware('permission:dashboard.view')
        ->name('dashboard.oee.machine');

    Route::get('/users', [UserController::class, 'index'])
        ->middleware('permission:users.view')
        ->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])
        ->middleware('permission:users.create')
        ->name('users.create');
    Route::post('/users', [UserController::class, 'store'])
        ->middleware('permission:users.create')
        ->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])
        ->middleware('permission:users.update')
        ->name('users.edit');
    Route::match(['put', 'patch'], '/users/{user}', [UserController::class, 'update'])
        ->middleware('permission:users.update')
        ->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:users.delete')
        ->name('users.destroy');

    Route::patch(
        '/users/{user}/toggle-active',
        [UserController::class, 'toggleActive']
    )
        ->middleware('permission:users.activate')
        ->name('users.toggle-active');

    Route::get('/roles', [RoleController::class, 'index'])
        ->middleware('permission:roles.view')
        ->name('roles.index');
    Route::get('/roles/create', [RoleController::class, 'create'])
        ->middleware('permission:roles.create')
        ->name('roles.create');
    Route::post('/roles', [RoleController::class, 'store'])
        ->middleware('permission:roles.create')
        ->name('roles.store');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])
        ->middleware('permission:roles.update')
        ->name('roles.edit');
    Route::match(['put', 'patch'], '/roles/{role}', [RoleController::class, 'update'])
        ->middleware('permission:roles.update')
        ->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
        ->middleware('permission:roles.delete')
        ->name('roles.destroy');

    Route::get(
        '/activity-logs',
        [ActivityLogController::class, 'index']
    )
        ->middleware('permission:activity-logs.view')
        ->name('activity-logs.index');

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

Route::middleware(['auth', 'active'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get(
            '/plcs/{plc}/tags/sync',
            PlcTagDashboardController::class
        )->middleware('permission:collector.view')
            ->name('plcs.tags.sync-dashboard');

        Route::post(
            '/plcs/{plc}/tags/sync',
            PlcTagSyncController::class
        )->middleware('permission:collector.manage')
            ->name('plcs.tags.sync');
    });

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/products', [ProductController::class, 'index'])
        ->middleware('permission:products.view')
        ->name('products.index');

    Route::get('/products/create', [ProductController::class, 'create'])
        ->middleware('permission:products.create')
        ->name('products.create');

    Route::post('/products', [ProductController::class, 'store'])
        ->middleware('permission:products.create')
        ->name('products.store');

    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])
        ->middleware('permission:products.update')
        ->name('products.edit');

    Route::put('/products/{product}', [ProductController::class, 'update'])
        ->middleware('permission:products.update')
        ->name('products.update');

    Route::delete('/products/{product}', [ProductController::class, 'destroy'])
        ->middleware('permission:products.delete')
        ->name('products.destroy');

    Route::post('/products-import', [ProductController::class, 'import'])
        ->middleware('permission:products.create')
        ->name('products.import');

    Route::get('/products-export', [ProductController::class, 'export'])
        ->middleware('permission:products.view')
        ->name('products.export');
});


Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/production-shifts', [ProductionShiftController::class, 'index'])->middleware('permission:production-shifts.view')->name('production-shifts.index');
    Route::get('/production-shifts/create', [ProductionShiftController::class, 'create'])->middleware('permission:production-shifts.create')->name('production-shifts.create');
    Route::post('/production-shifts', [ProductionShiftController::class, 'store'])->middleware('permission:production-shifts.create')->name('production-shifts.store');
    Route::get('/production-shifts/{productionShift}/edit', [ProductionShiftController::class, 'edit'])->middleware('permission:production-shifts.update')->name('production-shifts.edit');
    Route::put('/production-shifts/{productionShift}', [ProductionShiftController::class, 'update'])->middleware('permission:production-shifts.update')->name('production-shifts.update');
    Route::delete('/production-shifts/{productionShift}', [ProductionShiftController::class, 'destroy'])->middleware('permission:production-shifts.delete')->name('production-shifts.destroy');

    Route::get('/machine-product-standards', [MachineProductStandardController::class, 'index'])->middleware('permission:machine-product-standards.view')->name('machine-product-standards.index');
    Route::get('/machine-product-standards/create', [MachineProductStandardController::class, 'create'])->middleware('permission:machine-product-standards.create')->name('machine-product-standards.create');
    Route::post('/machine-product-standards', [MachineProductStandardController::class, 'store'])->middleware('permission:machine-product-standards.create')->name('machine-product-standards.store');
    Route::get('/machine-product-standards/{machineProductStandard}/edit', [MachineProductStandardController::class, 'edit'])->middleware('permission:machine-product-standards.update')->name('machine-product-standards.edit');
    Route::put('/machine-product-standards/{machineProductStandard}', [MachineProductStandardController::class, 'update'])->middleware('permission:machine-product-standards.update')->name('machine-product-standards.update');
    Route::delete('/machine-product-standards/{machineProductStandard}', [MachineProductStandardController::class, 'destroy'])->middleware('permission:machine-product-standards.delete')->name('machine-product-standards.destroy');
    Route::post('/machine-product-standards-import', [MachineProductStandardController::class, 'import'])->middleware('permission:machine-product-standards.create')->name('machine-product-standards.import');
    Route::get('/machine-product-standards-export', [MachineProductStandardController::class, 'export'])->middleware('permission:machine-product-standards.view')->name('machine-product-standards.export');
});


Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/downtime-reasons', [DowntimeReasonController::class, 'index'])->middleware('permission:downtime-reasons.view')->name('downtime-reasons.index');
    Route::get('/downtime-reasons/create', [DowntimeReasonController::class, 'create'])->middleware('permission:downtime-reasons.create')->name('downtime-reasons.create');
    Route::post('/downtime-reasons', [DowntimeReasonController::class, 'store'])->middleware('permission:downtime-reasons.create')->name('downtime-reasons.store');
    Route::get('/downtime-reasons/{downtimeReason}/edit', [DowntimeReasonController::class, 'edit'])->middleware('permission:downtime-reasons.update')->name('downtime-reasons.edit');
    Route::put('/downtime-reasons/{downtimeReason}', [DowntimeReasonController::class, 'update'])->middleware('permission:downtime-reasons.update')->name('downtime-reasons.update');
    Route::delete('/downtime-reasons/{downtimeReason}', [DowntimeReasonController::class, 'destroy'])->middleware('permission:downtime-reasons.delete')->name('downtime-reasons.destroy');
    Route::post('/downtime-reasons-import', [DowntimeReasonController::class, 'import'])->middleware('permission:downtime-reasons.create')->name('downtime-reasons.import');
    Route::get('/downtime-reasons-export', [DowntimeReasonController::class, 'export'])->middleware('permission:downtime-reasons.view')->name('downtime-reasons.export');
});

Route::middleware(['auth', 'active'])->group(function (): void {

    Route::get(
        '/production-declarations',
        [ProductionDeclarationController::class, 'index']
    )
        ->middleware('permission:production-declarations.view')
        ->name('production-declarations.index');

    Route::get(
        '/production-declarations/create',
        [ProductionDeclarationController::class, 'create']
    )
        ->middleware('permission:production-declarations.create')
        ->name('production-declarations.create');

    Route::post(
        '/production-declarations',
        [ProductionDeclarationController::class, 'store']
    )
        ->middleware('permission:production-declarations.create')
        ->name('production-declarations.store');

    Route::get(
        '/production-declarations/product-search',
        [ProductionDeclarationController::class, 'productSearch']
    )
        ->middleware('permission:production-declarations.view')
        ->name('production-declarations.product-search');

    Route::get(
        '/production-declarations/product-options',
        [ProductionDeclarationController::class, 'productOptions']
    )
        ->middleware('permission:production-declarations.create')
        ->name('production-declarations.product-options');

    Route::get(
        '/production-declarations/duplicate-check',
        [ProductionDeclarationController::class, 'duplicateCheck']
    )
        ->middleware('permission:production-declarations.view')
        ->name('production-declarations.duplicate-check');

    Route::get(
        '/production-declarations/{productionDeclaration}/edit',
        [ProductionDeclarationController::class, 'edit']
    )
        ->middleware('permission:production-declarations.update')
        ->name('production-declarations.edit');

    Route::put(
        '/production-declarations/{productionDeclaration}',
        [ProductionDeclarationController::class, 'update']
    )
        ->middleware('permission:production-declarations.update')
        ->name('production-declarations.update');

    Route::patch(
        '/production-declarations/{productionDeclaration}/submit',
        [ProductionDeclarationController::class, 'submit']
    )
        ->middleware('permission:production-declarations.update')
        ->name('production-declarations.submit');

    Route::patch(
        '/production-declarations/{productionDeclaration}/return',
        [ProductionDeclarationController::class, 'returnForCorrection']
    )
        ->middleware('permission:production-declarations.approve')
        ->name('production-declarations.return');

    Route::patch(
        '/production-declarations/{productionDeclaration}/lock',
        [ProductionDeclarationController::class, 'lock']
    )
        ->middleware('permission:production-declarations.lock')
        ->name('production-declarations.lock');

    Route::patch(
        '/production-declarations/{productionDeclaration}/unlock',
        [ProductionDeclarationController::class, 'unlock']
    )
        ->middleware('permission:production-declarations.unlock')
        ->name('production-declarations.unlock');

    Route::delete(
        '/production-declarations/{productionDeclaration}',
        [ProductionDeclarationController::class, 'destroy']
    )
        ->middleware('permission:production-declarations.delete')
        ->name('production-declarations.destroy');

    Route::patch(
        '/production-declarations/{productionDeclaration}/restore',
        [ProductionDeclarationController::class, 'restore']
    )
        ->middleware('permission:production-declarations.restore')
        ->name('production-declarations.restore');
    Route::get(
        '/production-declarations/{productionDeclaration}',
        [ProductionDeclarationController::class, 'show']
    )
        ->middleware('permission:production-declarations.view')
        ->name('production-declarations.show');

    Route::patch(
        '/production-declarations/{productionDeclaration}/approve',
        [ProductionDeclarationController::class, 'approve']
    )
        ->middleware('permission:production-declarations.approve')
        ->name('production-declarations.approve');


    Route::match(
        ['post', 'patch'],
        '/production-declarations/{productionDeclaration}/recalculate-oee',
        [ProductionDeclarationController::class, 'recalculate']
    )
        ->middleware('permission:oee.recalculate')
        ->name('production-declarations.recalculate-oee');

    Route::get('/oee-reports', [OeeReportController::class, 'index'])
        ->middleware('permission:oee.view')
        ->name('oee-reports.index');


    Route::get(
        '/production-reports/daily-detail',
        [DailyProductionReportController::class, 'index']
    )
        ->middleware('permission:reports.view')
        ->name('production-reports.daily-detail');

    Route::get(
        '/production-reports/daily-detail/excel',
        [DailyProductionReportController::class, 'exportExcel']
    )
        ->middleware('permission:reports.export')
        ->name('production-reports.daily-detail.excel');

    Route::get(
        '/oee-settings',
        [MachineOeeSettingController::class, 'index']
    )
        ->middleware('permission:plc-tags.configure-oee')
        ->name('oee-settings.index');

    Route::post(
        '/plcs/{plc}/collector-token',
        [PlcCollectorTokenController::class, 'regenerate']
    )
        ->middleware('permission:plcs.update')
        ->name('plcs.collector-token');

    Route::get(
        '/machines/{machine}/oee-settings',
        [MachineOeeSettingController::class, 'edit']
    )
        ->middleware('permission:plc-tags.configure-oee')
        ->name('machines.oee-settings.edit');

    Route::put(
        '/machines/{machine}/oee-settings',
        [MachineOeeSettingController::class, 'update']
    )
        ->middleware('permission:plc-tags.configure-oee')
        ->name('machines.oee-settings.update');

    Route::get(
        '/machines/{machineCode}/tags',
        [MachineTagController::class, 'index']
    )->middleware('permission:plc-tags.view')
        ->name('machines.tags.index');

    Route::post(
        '/machines/{machineCode}/tags/bulk-update',
        [MachineTagController::class, 'bulkUpdate']
    )->middleware('permission:plc-tags.update')
        ->name('machines.tags.bulk-update');

    Route::get(
        '/machines/{machineCode}/tags/{tag}',
        [MachineTagController::class, 'edit']
    )->middleware('permission:plc-tags.view')
        ->name('machines.tags.edit');

    Route::put(
        '/machines/{machineCode}/tags/{tag}',
        [MachineTagController::class, 'update']
    )->middleware('permission:plc-tags.update')
        ->name('machines.tags.update');

    Route::get(
        '/machines/{machineCode}/monitoring-layout',
        [MonitoringLayoutEditorController::class, 'index']
    )->middleware('permission:plc-tags.view')
        ->name('monitoring-layout.index');

    Route::put(
        '/machines/{machineCode}/monitoring-layout/tags/{tag}',
        [MonitoringLayoutEditorController::class, 'updateTag']
    )->middleware('permission:plc-tags.update')
        ->name('monitoring-layout.tags.update');

    Route::post(
        '/machines/{machineCode}/monitoring-layout/rows',
        [MonitoringLayoutController::class, 'storeRow']
    )->middleware('permission:plc-tags.update')
        ->name('monitoring-layout.rows.store');

    Route::post(
        '/machines/{machineCode}/monitoring-layout/columns',
        [MonitoringLayoutController::class, 'storeColumn']
    )->middleware('permission:plc-tags.update')
        ->name('monitoring-layout.columns.store');

    // Màn hình monitoring tổng quan hiện có.
    Route::get(
        '/monitoring/{machineCode}',
        [MonitoringController::class, 'show']
    )->middleware('permission:monitoring.view')
        ->name('monitoring.show');

    // Runtime công nghệ động; chỉ khai báo duy nhất tại đây.
    Route::get(
        '/monitoring/{machineCode}/technology',
        [TechnologyMonitoringController::class, 'index']
    )->middleware('permission:monitoring.view')
        ->name('monitoring.technology');

    Route::get(
        '/monitoring/{machineCode}/technology/data',
        [TechnologyMonitoringController::class, 'data']
    )->middleware('permission:monitoring.view')
        ->name('monitoring.technology.data');

    Route::get(
        '/monitoring/{machineCode}/technology/tags/{tag}/trend',
        [TechnologyMonitoringController::class, 'trend']
    )->middleware('permission:monitoring.trend')
        ->name('monitoring.technology.trend');
});

require __DIR__ . '/auth.php';
require __DIR__ . '/device.php';
require __DIR__ . '/collector_admin.php';

