@echo off
setlocal
cd /d "%~dp0"

set "HOST=127.0.0.1"
set "PORT=8080"
if defined DEV_HOST set "HOST=%DEV_HOST%"
if defined DEV_PORT set "PORT=%DEV_PORT%"

where php >nul 2>&1
if errorlevel 1 (
    echo ERROR: php is not in PATH. Install PHP 8.3+ and add it to PATH.
    exit /b 1
)

if not exist ".env" (
    echo WARNING: .env not found. Copy .env.example to .env and configure it.
)

if not exist "web\dist\production\login.html" (
    echo WARNING: web\dist\production\login.html not found.
    echo          Build the frontend first:  cd web ^&^& npm install ^&^& npm run build
    echo.
)

echo EpiElla dev server
echo   URL:   http://%HOST%:%PORT%/login.html
echo   Stop:  Ctrl+C
echo.

php -S %HOST%:%PORT% -t api\public api\public\router.php
