@echo off
echo ====================================
echo Starting SiPadu Development Server
echo ====================================
echo.

REM Check if server is already running
netstat -ano | findstr ":8000" >nul
if %errorlevel% equ 0 (
    echo [INFO] Server already running on port 8000
    echo.
    goto :open_browser
)

REM Start Laravel development server
echo [1/2] Starting Laravel server...
start /B cmd /c "php artisan serve --host=127.0.0.1 --port=8000"

timeout /t 3 >nul

:open_browser
REM Open browser
echo [2/2] Opening browser...
start http://127.0.0.1:8000

echo.
echo ====================================
echo SiPadu Development Environment Ready
echo ====================================
echo Web: http://127.0.0.1:8000
echo.
echo Login Credentials:
echo - Admin: admin@sipadu.go.id / Admin@123456
echo - PA Assistant: asisten@pa-painan.go.id / Pass@12345
echo ====================================
echo.
pause
