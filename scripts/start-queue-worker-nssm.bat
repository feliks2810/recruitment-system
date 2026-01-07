@echo off
REM Script untuk install Queue Worker sebagai Windows Service menggunakan NSSM
REM Jalankan sebagai Administrator

setlocal enabledelayedexpansion

set PROJECT_PATH=C:\xampp\htdocs\recruitment-system
set SERVICE_NAME=RecruitmentQueueWorker
set PHP_PATH=C:\xampp\php\php.exe
set NSSM_PATH=%~dp0..\vendor\bin\nssm.exe

if not exist "%NSSM_PATH%" (
    echo NSSM tidak ditemukan di vendor/bin
    echo Install dulu: composer require winbinder/nssm --dev
    pause
    exit /b 1
)

echo Menginstall %SERVICE_NAME% sebagai Windows Service...

REM Install service
"%NSSM_PATH%" install %SERVICE_NAME% "%PHP_PATH%" "artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=1200"

REM Set working directory
"%NSSM_PATH%" set %SERVICE_NAME% AppDirectory "%PROJECT_PATH%"

REM Set restart behavior
"%NSSM_PATH%" set %SERVICE_NAME% AppRestartDelay 5000
"%NSSM_PATH%" set %SERVICE_NAME% AppThrottle 1500

REM Set logging
"%NSSM_PATH%" set %SERVICE_NAME% AppStdout "!PROJECT_PATH!\storage\logs\queue-worker.log"
"%NSSM_PATH%" set %SERVICE_NAME% AppStderr "!PROJECT_PATH!\storage\logs\queue-worker-error.log"

REM Start service
net start %SERVICE_NAME%

echo.
echo Service %SERVICE_NAME% berhasil diinstall dan dijalankan!
echo.
echo Untuk menghentikan: net stop %SERVICE_NAME%
echo Untuk meremove: "%NSSM_PATH%" remove %SERVICE_NAME% confirm
pause
