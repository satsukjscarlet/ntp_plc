$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$vendorRoot = Join-Path $projectRoot 'public\vendor'

$files = @(
    @{
        Url = 'https://code.jquery.com/jquery-3.7.1.min.js'
        Target = Join-Path $vendorRoot 'jquery\jquery.min.js'
    },
    @{
        Url = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css'
        Target = Join-Path $vendorRoot 'select2\css\select2.min.css'
    },
    @{
        Url = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js'
        Target = Join-Path $vendorRoot 'select2\js\select2.min.js'
    },
    @{
        Url = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/vi.js'
        Target = Join-Path $vendorRoot 'select2\js\i18n\vi.js'
    }
)

foreach ($file in $files) {
    $directory = Split-Path -Parent $file.Target
    New-Item -ItemType Directory -Force -Path $directory | Out-Null
    Write-Host "Downloading $($file.Url)" -ForegroundColor Cyan
    Invoke-WebRequest -Uri $file.Url -OutFile $file.Target -UseBasicParsing

    if (-not (Test-Path $file.Target) -or (Get-Item $file.Target).Length -lt 100) {
        throw "Downloaded file is invalid: $($file.Target)"
    }
}

Write-Host ''
Write-Host 'Đã cài jQuery và Select2 vào public/vendor.' -ForegroundColor Green
Write-Host 'Từ bây giờ màn hình nhập liệu không cần Internet để tải thư viện.' -ForegroundColor Green
