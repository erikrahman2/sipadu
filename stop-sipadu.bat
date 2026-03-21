@echo off
echo ====================================
echo Stopping SiPadu Development Server
echo ====================================
echo.

REM Kill processes on port 8000
for /f "tokens=5" %%a in ('netstat -ano ^| findstr :8000') do (
    echo Stopping process %%a...
    taskkill /F /PID %%a 2>nul
)

echo.
echo [DONE] Server stopped
echo.
pause
