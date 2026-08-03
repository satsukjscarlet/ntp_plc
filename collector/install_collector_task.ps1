param(
    [string]$ProjectPath = "D:\xampp\htdocs\NTP_PLC",
    [string]$TaskName = "NTP_PLC_Collector_M62"
)

$ErrorActionPreference = "Stop"

$collectorPath = Join-Path $ProjectPath "collector"
$runnerPath = Join-Path $collectorPath "run_collector_service.bat"
$pythonPath = Join-Path $collectorPath ".venv\Scripts\python.exe"
$envPath = Join-Path $collectorPath ".env"

Write-Host "=== Cai dat collector tu khoi dong Windows ===" -ForegroundColor Cyan

foreach ($requiredPath in @($collectorPath, $runnerPath, $pythonPath, $envPath)) {
    if (-not (Test-Path $requiredPath)) {
        throw "Khong tim thay: $requiredPath"
    }
}

# Xóa task cũ để việc cài đặt có thể chạy lại an toàn.
$existingTask = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
if ($existingTask) {
    Write-Host "Da ton tai task $TaskName, dang thay the..." -ForegroundColor Yellow
    Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
}

$action = New-ScheduledTaskAction `
    -Execute "cmd.exe" `
    -Argument "/c `"$runnerPath`"" `
    -WorkingDirectory $collectorPath

# Chạy khi Windows khởi động. Restart vô hạn nếu process lỗi.
$trigger = New-ScheduledTaskTrigger -AtStartup

$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -RestartCount 999 `
    -RestartInterval (New-TimeSpan -Minutes 1) `
    -ExecutionTimeLimit (New-TimeSpan -Days 3650)

# Chạy bằng tài khoản SYSTEM, không cần đăng nhập Windows.
$principal = New-ScheduledTaskPrincipal `
    -UserId "SYSTEM" `
    -LogonType ServiceAccount `
    -RunLevel Highest

$task = New-ScheduledTask `
    -Action $action `
    -Trigger $trigger `
    -Settings $settings `
    -Principal $principal `
    -Description "Thu thap du lieu PLC M62 va gui ve Laravel NTP_PLC lien tuc."

Register-ScheduledTask -TaskName $TaskName -InputObject $task | Out-Null

Start-ScheduledTask -TaskName $TaskName
Start-Sleep -Seconds 3

$taskInfo = Get-ScheduledTaskInfo -TaskName $TaskName
$taskState = (Get-ScheduledTask -TaskName $TaskName).State

Write-Host ""
Write-Host "Cai dat thanh cong." -ForegroundColor Green
Write-Host "Task:       $TaskName"
Write-Host "Trang thai: $taskState"
Write-Host "Lan chay:   $($taskInfo.LastRunTime)"
Write-Host "Ma ket qua: $($taskInfo.LastTaskResult)"
Write-Host ""
Write-Host "Log collector:"
Write-Host "  $collectorPath\logs\collector.log"
Write-Host "  $collectorPath\logs\console.log"
