@echo off
REM Script untuk menjalankan Queue Worker di Windows
REM Letakkan shortcut dari file ini di folder Startup Windows untuk auto-start

cd /d "C:\xampp\htdocs\recruitment-system"

REM Jalankan queue worker di background
start "Recruitment Queue Worker" php artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=1200

echo Queue Worker dimulai
pause
