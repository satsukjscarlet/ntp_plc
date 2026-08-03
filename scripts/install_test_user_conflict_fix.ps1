$ErrorActionPreference = "Stop"

$projectRoot = Split-Path -Parent $PSScriptRoot
$seederFile = Join-Path $projectRoot "database\seeders\M62JulyProductionTestSeeder.php"

if (!(Test-Path $seederFile)) {
    throw "Khong tim thay M62JulyProductionTestSeeder.php"
}

$content = Get-Content $seederFile -Raw -Encoding UTF8
$content = $content.Replace(
    "->where('username', 'vanhanh')",
    "->where('username', 'test_vanhanh')"
)
$content = $content.Replace(
    "->where('username', 'quandoc')",
    "->where('username', 'test_quandoc')"
)
$content = $content.Replace(
    "->where('email', 'vanhanh.test@ntp.local')",
    "->where('username', 'test_vanhanh')"
)
$content = $content.Replace(
    "->where('email', 'quandoc.test@ntp.local')",
    "->where('username', 'test_quandoc')"
)

Set-Content $seederFile $content -Encoding UTF8

Set-Location $projectRoot

composer dump-autoload
php artisan optimize:clear
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=NtpPlcTestUserSeeder
php artisan permission:cache-reset

Write-Host ""
Write-Host "Da tao tai khoan test. Kiem tra bang lenh:" -ForegroundColor Green
Write-Host "php artisan db:seed --class=M62JulyProductionTestSeeder"
