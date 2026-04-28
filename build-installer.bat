@echo off
REM Library Management System - Build Desktop Installer

REM Check if Node.js is installed
where node >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: Node.js is not installed
    pause
    exit /b 1
)

echo.
echo ============================================
echo   Building Library Management System
echo ============================================
echo.

REM Install dependencies if needed
if not exist "node_modules" (
    echo Installing dependencies...
    call npm install
)

echo.
echo Building Windows installer...
call npm run build-win

if %errorlevel% equ 0 (
    echo.
    echo ============================================
    echo   Build Complete!
    echo ============================================
    echo.
    echo Installers created in 'dist' folder:
    echo - Library Management System Setup 1.0.0.exe (Installer)
    echo - Library Management System 1.0.0.exe (Portable)
    echo.
) else (
    echo.
    echo Build failed. Please check the errors above.
    echo.
)

pause
