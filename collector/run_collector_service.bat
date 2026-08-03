@echo off
setlocal

cd /d "%~dp0"

if not exist ".venv\Scripts\python.exe" (
    echo [%date% %time%] Khong tim thay .venv\Scripts\python.exe >> logs\startup-error.log
    exit /b 1
)

if not exist "logs" mkdir logs

echo [%date% %time%] Khoi dong NTP_PLC Collector... >> logs\startup.log

".venv\Scripts\python.exe" collector.py >> logs\console.log 2>&1

set EXIT_CODE=%ERRORLEVEL%
echo [%date% %time%] Collector da dung, exit code %EXIT_CODE%. >> logs\startup-error.log

exit /b %EXIT_CODE%
