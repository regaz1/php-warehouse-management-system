@echo off
setlocal
cd /d "%~dp0"
title My Warehouse - PHP

set "PHP_EXE="
if exist "runtime\php\php.exe" set "PHP_EXE=%~dp0runtime\php\php.exe"
if not defined PHP_EXE if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"
if not defined PHP_EXE (
    where php >nul 2>&1
    if not errorlevel 1 set "PHP_EXE=php"
)

if not defined PHP_EXE call :install_php
if not defined PHP_EXE goto :setup_failed

"%PHP_EXE%" -r "exit(extension_loaded('pdo_sqlite') ? 0 : 1);"
if errorlevel 1 (
    set "PHP_EXE="
    call :install_php
)
if not defined PHP_EXE goto :sqlite_failed

powershell -NoProfile -Command "try { Invoke-WebRequest -Uri 'http://127.0.0.1:8000' -UseBasicParsing -TimeoutSec 1 | Out-Null; exit 0 } catch { exit 1 }" >nul 2>&1
if not errorlevel 1 (
    echo The application is already running.
    start "" "http://127.0.0.1:8000"
    exit /b 0
)

echo.
echo Starting My Warehouse...
echo Open: http://127.0.0.1:8000
echo Keep this window open. Press Ctrl+C to stop.
echo.
start "" powershell -NoProfile -WindowStyle Hidden -Command "Start-Sleep -Seconds 2; Start-Process 'http://127.0.0.1:8000'"
"%PHP_EXE%" -S 127.0.0.1:8000 -t public
goto :end

:install_php
echo.
echo PHP was not found. Preparing the portable PHP runtime...
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0setup-php.ps1"
if errorlevel 1 exit /b 1
if exist "runtime\php\php.exe" set "PHP_EXE=%~dp0runtime\php\php.exe"
exit /b 0

:setup_failed
echo.
echo PHP setup failed. Check your internet connection or install XAMPP.
goto :pause_error

:sqlite_failed
echo.
echo PHP exists, but PDO SQLite is not available.
goto :pause_error

:pause_error
echo.
pause

:end
endlocal
