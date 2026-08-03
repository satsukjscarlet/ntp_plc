param(
    [string]$ProjectPath = "D:\xampp\htdocs\NTP_PLC",
    [string]$TaskName = "NTP_PLC_Collector_M62"
)

$collectorPath = Join-Path $ProjectPath "collector"

Write-Host "=== Trang thai Scheduled Task ===" -ForegroundColor Cyan
$task = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue

if (-not $task) {
    Write-Host "Chua cai dat task $TaskName." -ForegroundColor Red
    exit 1
}

$info = Get-ScheduledTaskInfo -TaskName $TaskName

Write-Host "Task:            $TaskName"
Write-Host "State:           $($task.State)"
Write-Host "LastRunTime:     $($info.LastRunTime)"
Write-Host "LastTaskResult:  $($info.LastTaskResult)"
Write-Host "NextRunTime:     $($info.NextRunTime)"

Write-Host ""
Write-Host "=== Process Python collector ===" -ForegroundColor Cyan

$processes = Get-CimInstance Win32_Process |
    Where-Object {
        $_.Name -match "^python(w)?\.exe$" -and
        $_.CommandLine -like "*collector.py*"
    }

if ($processes) {
    $processes | Select-Object ProcessId, Name, CommandLine | Format-List
} else {
    Write-Host "Khong thay process collector.py dang chay." -ForegroundColor Red
}

Write-Host ""
Write-Host "=== 20 dong log gan nhat ===" -ForegroundColor Cyan
$logPath = Join-Path $collectorPath "logs\collector.log"

if (Test-Path $logPath) {
    Get-Content $logPath -Tail 20
} else {
    Write-Host "Chua co log: $logPath" -ForegroundColor Yellow
}
