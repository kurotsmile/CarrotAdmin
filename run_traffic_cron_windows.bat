@echo off
setlocal

set "ADMIN_DIR=%~dp0"
set "PHP_EXE="
set "MAX_DAYS=0"
set "LOG_DIR=%ADMIN_DIR%logs"

if not exist "%LOG_DIR%" mkdir "%LOG_DIR%"

for %%P in (
    "%ADMIN_DIR%..\php\php.exe"
    "C:\xampp\php\php.exe"
    "D:\xampp\php\php.exe"
    "E:\xampp\php\php.exe"
    "C:\laragon\bin\php\php.exe"
    "D:\laragon\bin\php\php.exe"
    "E:\laragon\bin\php\php.exe"
) do if exist "%%~fP" set "PHP_EXE=%%~fP"

for /d %%P in (
    "C:\laragon\bin\php\php-*"
    "D:\laragon\bin\php\php-*"
    "E:\laragon\bin\php\php-*"
) do if exist "%%~fP\php.exe" set "PHP_EXE=%%~fP\php.exe"

if not defined PHP_EXE for %%P in (php.exe) do set "PHP_EXE=%%~$PATH:P"

if not defined PHP_EXE (
    echo [%date% %time%] Khong tim thay php.exe. Hay cai XAMPP/Laragon hoac sua PHP_EXE trong file nay. >> "%LOG_DIR%\traffic_cron_windows.log"
    echo Khong tim thay php.exe.
    echo Hay cai XAMPP/Laragon hoac sua bien PHP_EXE trong file:
    echo "%~f0"
    pause
    exit /b 1
)

cd /d "%ADMIN_DIR%"
"%PHP_EXE%" -d max_execution_time=0 -d memory_limit=1024M "%ADMIN_DIR%cron_traffic_report.php" --delete-raw=1 --delete-created=1 --max-days=%MAX_DAYS% >> "%LOG_DIR%\traffic_cron_windows.log" 2>&1

endlocal
