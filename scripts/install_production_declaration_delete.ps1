$ErrorActionPreference = "Stop"

$projectRoot = Split-Path -Parent $PSScriptRoot
$routeFile = Join-Path $projectRoot "routes\web.php"

if (!(Test-Path $routeFile)) {
    throw "Khong tim thay routes\web.php"
}

$content = Get-Content $routeFile -Raw -Encoding UTF8

if ($content -notmatch "production-declarations\.destroy") {
    $anchor = @"
    Route::get(
        '/production-declarations/{productionDeclaration}',
        [ProductionDeclarationController::class, 'show']
    )
"@

    $routes = @"
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

"@

    if ($content.Contains($anchor)) {
        $content = $content.Replace($anchor, $routes + $anchor)
    } else {
        throw "Khong tim thay vi tri chen route production declaration."
    }

    Set-Content $routeFile $content -Encoding UTF8
    Write-Host "Da them route xoa va khoi phuc." -ForegroundColor Green
} else {
    Write-Host "Route xoa/khoi phuc da ton tai." -ForegroundColor Yellow
}

Set-Location $projectRoot

composer dump-autoload
php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
php artisan permission:cache-reset

php artisan route:list --json |
    Out-String |
    Select-String "production-declarations.destroy|production-declarations.restore"
