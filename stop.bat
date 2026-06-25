@echo off
cls
echo ========================================
echo   STOPPING PKG PRESENSI APPLICATION
echo ========================================
echo.

echo Stopping all PHP processes...
taskkill /F /IM php.exe 2>nul
if %errorlevel% equ 0 (
    echo [OK] PHP stopped
) else (
    echo [INFO] No PHP process running
)

echo.
echo Stopping all Node processes...
taskkill /F /IM node.exe 2>nul
if %errorlevel% equ 0 (
    echo [OK] Node stopped
) else (
    echo [INFO] No Node process running
)

echo.
echo ========================================
echo   ALL SERVICES STOPPED
echo ========================================
echo.
pause
