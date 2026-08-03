param(
    [string]$TaskName = "NTP_PLC_Collector_M62"
)

$ErrorActionPreference = "Stop"

$task = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue

if (-not $task) {
    throw "Khong tim thay task $TaskName."
}

Stop-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue

# Đảm bảo process collector cũ không còn treo.
Get-CimInstance Win32_Process |
    Where-Object {
        $_.Name -match "^python(w)?\.exe$" -and
        $_.CommandLine -like "*collector.py*"
    } |
    ForEach-Object {
        Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue
    }

Start-Sleep -Seconds 2
Start-ScheduledTask -TaskName $TaskName
Start-Sleep -Seconds 3

$state = (Get-ScheduledTask -TaskName $TaskName).State
Write-Host "Da khoi dong lai collector. Trang thai task: $state" -ForegroundColor Green
