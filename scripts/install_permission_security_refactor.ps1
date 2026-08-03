$ErrorActionPreference = "Stop"

$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

Write-Host "=== Composer / cache ===" -ForegroundColor Cyan
composer dump-autoload
php artisan optimize:clear

Write-Host "`n=== Seed lai ma tran quyen ===" -ForegroundColor Cyan
php artisan db:seed --class=RolePermissionSeeder
php artisan permission:cache-reset

Write-Host "`n=== Kiem tra route quan trong ===" -ForegroundColor Cyan

$routeJson = php artisan route:list --json | Out-String
$routes = $routeJson | ConvertFrom-Json

$expectations = @{
    "users.index" = "users.view"
    "users.destroy" = "users.delete"
    "roles.index" = "roles.view"
    "roles.destroy" = "roles.delete"
    "machines.index" = "machines.view"
    "machines.destroy" = "machines.delete"
    "plcs.index" = "plcs.view"
    "plc-tags.update" = "plc-tags.update"
    "machines.tags.bulk-update" = "plc-tags.update"
    "monitoring-layout.tags.update" = "plc-tags.update"
    "monitoring.technology" = "monitoring.view"
    "monitoring.technology.trend" = "monitoring.trend"
    "production-declarations.recalculate-oee" = "oee.recalculate"
    "oee-reports.index" = "oee.view"
    "production-reports.daily-detail" = "reports.view"
    "production-reports.daily-detail.excel" = "reports.export"
    "admin.plcs.tags.sync" = "collector.manage"
}

$failed = $false

foreach ($entry in $expectations.GetEnumerator()) {
    $route = $routes |
        Where-Object { $_.name -eq $entry.Key } |
        Select-Object -First 1

    if (!$route) {
        Write-Host "[MISSING ROUTE] $($entry.Key)" -ForegroundColor Red
        $failed = $true
        continue
    }

    $middlewareText = ($route.middleware -join ",")

    # route:list --json tra middleware da resolve thanh:
    # Spatie\Permission\Middleware\PermissionMiddleware:users.view
    # Nen chi can kiem tra ten permission sau dau hai cham.
    $expectedSuffix = ":" + $entry.Value

    if ($middlewareText -match [regex]::Escape($expectedSuffix)) {
        Write-Host "[OK] $($entry.Key) -> $($entry.Value)" -ForegroundColor Green
    } else {
        Write-Host "[WRONG] $($entry.Key) missing $($entry.Value)" -ForegroundColor Red
        Write-Host "        $middlewareText"
        $failed = $true
    }
}

Write-Host "`n=== Permission audit ===" -ForegroundColor Cyan
php artisan permissions:audit-routes
$auditExit = $LASTEXITCODE

if ($failed -or $auditExit -ne 0) {
    Write-Host "`nKiem tra phan quyen chua dat." -ForegroundColor Red
    exit 1
}

Write-Host "`nTat ca route trong yeu da dung permission middleware." -ForegroundColor Green
