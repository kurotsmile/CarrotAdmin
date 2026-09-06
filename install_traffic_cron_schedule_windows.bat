@echo off
setlocal

set "ADMIN_DIR=%~dp0"
set "CRON_BAT=%ADMIN_DIR%run_traffic_cron_windows.bat"
set "TASK_NAME=CarrotAdmin Traffic Cron"
set "TASK_TIME=00:00"

if not exist "%CRON_BAT%" (
    echo Khong tim thay file cron: "%CRON_BAT%"
    pause
    exit /b 1
)

net session >nul 2>&1
if not "%errorlevel%"=="0" (
    echo Vui long click phai file nay va chon "Run as administrator".
    pause
    exit /b 1
)

schtasks /Create /TN "%TASK_NAME%" /TR "\"%CRON_BAT%\"" /SC DAILY /ST %TASK_TIME% /F
if not "%errorlevel%"=="0" (
    echo Tao lich cron that bai.
    pause
    exit /b 1
)

echo Da tao lich "%TASK_NAME%" chay moi ngay luc %TASK_TIME%:00.
echo File duoc chay: "%CRON_BAT%"
pause

endlocal
