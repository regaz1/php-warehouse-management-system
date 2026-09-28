@echo off
setlocal
cd /d "%~dp0"
title Apothiki PHP

set "PHP_EXE="
where php >nul 2>&1
if not errorlevel 1 set "PHP_EXE=php"
if exist "runtime\php\php.exe" set "PHP_EXE=%~dp0runtime\php\php.exe"
if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"

if not defined PHP_EXE goto :php_missing

"%PHP_EXE%" -r "exit(extension_loaded('pdo_sqlite') ? 0 : 1);"
if errorlevel 1 goto :sqlite_missing

powershell -NoProfile -Command "try { Invoke-WebRequest -Uri 'http://127.0.0.1:8000' -UseBasicParsing -TimeoutSec 1 | Out-Null; exit 0 } catch { exit 1 }" >nul 2>&1
if not errorlevel 1 (
    echo The application is already running.
    start "" "http://127.0.0.1:8000"
    exit /b 0
)

echo.
echo Starting the Greek PHP Warehouse...
echo Open: http://127.0.0.1:8000
echo Keep this window open. Press Ctrl+C to stop.
echo.
start "" powershell -NoProfile -WindowStyle Hidden -Command "Start-Sleep -Seconds 2; Start-Process 'http://127.0.0.1:8000'"
"%PHP_EXE%" -S 127.0.0.1:8000 -t public
goto :end

:php_missing
echo.
echo PHP was not found.
echo Install XAMPP or PHP, then run START-PHP.bat again.
echo XAMPP: https://www.apachefriends.org/
goto :pause_error

:sqlite_missing
echo.
echo PHP exists, but PDO SQLite is not enabled.
echo Enable extension=pdo_sqlite in php.ini and try again.
goto :pause_error

:pause_error
echo.
pause

:end
endlocal
