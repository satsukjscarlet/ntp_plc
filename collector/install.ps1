$ErrorActionPreference = "Stop"

Write-Host "Tạo Python virtual environment..." -ForegroundColor Cyan
python -m venv .venv

Write-Host "Kích hoạt virtual environment..." -ForegroundColor Cyan
& .\.venv\Scripts\Activate.ps1

Write-Host "Nâng cấp pip..." -ForegroundColor Cyan
python -m pip install --upgrade pip

Write-Host "Cài thư viện..." -ForegroundColor Cyan
pip install -r requirements.txt

if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
    Write-Host "Đã tạo file .env. Hãy điền COLLECTOR_TOKEN." -ForegroundColor Yellow
}

Write-Host "Hoàn thành." -ForegroundColor Green
Write-Host "Chạy thử: python collector.py --dry-run"
