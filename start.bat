@echo off
setlocal

cd /d "%~dp0"

echo Starting The Oaks Compliance Tracker local server...
echo.
echo Local URL:
echo http://localhost:8000/license/login.php
echo.
echo Press Ctrl+C in this window to stop the server.
echo.

start "" "http://localhost:8000/license/login.php"

php -S localhost:8000 -t public_html

endlocal