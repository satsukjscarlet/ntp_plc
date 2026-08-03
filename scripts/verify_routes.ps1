$ErrorActionPreference = "Stop"

Set-Location (Split-Path -Parent $PSScriptRoot)

Write-Host "=== Clear cache ===" -ForegroundColor Cyan
php artisan optimize:clear

Write-Host "`n=== Required route names ===" -ForegroundColor Cyan
$required = @(
    "production-declarations.product-search",
    "production-declarations.product-options",
    "production-declarations.duplicate-check",
    "production-declarations.recalculate-oee",
    "oee-settings.index",
    "machines.oee-settings.edit",
    "machines.oee-settings.update",
    "monitoring.technology",
    "monitoring.technology.data",
    "monitoring.technology.trend",
    "monitoring-layout.index",
    "monitoring-layout.tags.update",
    "monitoring-layout.rows.store",
    "monitoring-layout.columns.store",
    "plcs.collector-token",
    "dashboard",
    "dashboard.oee.machine"
)

$routeText = php artisan route:list 2>&1 | Out-String

$missing = @()
foreach ($name in $required) {
    if ($routeText -match [regex]::Escape($name)) {
        Write-Host "[OK] $name" -ForegroundColor Green
    } else {
        Write-Host "[MISSING] $name" -ForegroundColor Red
        $missing += $name
    }
}

Write-Host "`n=== Blade named-route audit ===" -ForegroundColor Cyan
php artisan routes:audit-views

if ($missing.Count -gt 0) {
    Write-Host "`nCòn thiếu $($missing.Count) route bắt buộc." -ForegroundColor Red
    exit 1
}

Write-Host "`nTất cả route bắt buộc đã có." -ForegroundColor Green
