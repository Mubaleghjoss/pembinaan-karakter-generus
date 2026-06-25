@echo off
cls
echo ========================================
echo   STARTING PKG PRESENSI APPLICATION
echo ========================================
echo.

echo [1/4] Stopping existing services...
echo.

REM Kill existing PHP and npm processes
taskkill /F /IM php.exe 2>nul
taskkill /F /IM node.exe 2>nul
timeout /t 2 /nobreak >nul

echo [2/4] Starting Laravel Backend (http://127.0.0.1:8000)...
echo.
start "Laravel Server" cmd /k "cd /d %~dp0 && php artisan serve"
timeout /t 3 /nobreak >nul

echo [3/4] Starting Vite Frontend Dev Server...
echo.
start "Vite Dev" cmd /k "cd /d %~dp0 && npm run dev"
timeout /t 5 /nobreak >nul

echo [4/4] Checking services...
echo.

REM Test Laravel
echo Testing Laravel API...
curl -s http://127.0.0.1:8000 >nul
if %errorlevel% equ 0 (
    echo [OK] Laravel running at http://127.0.0.1:8000
) else (
    echo [ERROR] Laravel not responding!
)

echo.
echo Testing Vite Dev Server...
curl -s http://127.0.0.1:5173 >nul
if %errorlevel% equ 0 (
    echo [OK] Vite running at http://127.0.0.1:5173
) else (
    echo [WARNING] Vite may still be starting...
)

echo.
echo ========================================
echo   APPLICATION STARTED!
echo ========================================
echo.
echo Backend:  http://127.0.0.1:8000
echo Vite:     http://127.0.0.1:5173
echo.
echo Login:
echo - gunakan akun lokal yang sudah disiapkan di environment ini
echo - jangan simpan kredensial contoh di script bersama
echo.
echo Test QR:  http://127.0.0.1:8000/test-qr
echo Scanner:  http://127.0.0.1:8000/scan-presensi
echo.
echo Press any key to open browser...
pause >nul

start http://127.0.0.1:8000

echo.
echo Services are running in separate windows.
echo Close those windows to stop the services.
echo.
pause
