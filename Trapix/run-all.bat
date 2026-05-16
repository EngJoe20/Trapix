@echo off
REM Run all Trapix development services from the Laravel app root.
REM Usage: cmd /c run-all.bst

cd /d "%~dp0"

start "Trapix Server" cmd /k "php artisan serve"
start "Trapix Queue" cmd /k "php artisan queue:listen --tries=1 --timeout=360"
start "Trapix Vite" cmd /k "npm run dev"
start "Trapix Reverb" cmd /k "php artisan reverb:star"

echo All Trapix services started.
pause
