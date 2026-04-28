@echo off
REM Library Management System - Desktop Application Launcher

REM Check if Node.js is installed
where node >nul 2>nul
if %errorlevel% neq 0 (
    echo.
    echo ERROR: Node.js is not installed or not in PATH
    echo.
    echo Please download and install Node.js from: https://nodejs.org/
    echo Make sure to check "Add to PATH" during installation
    echo.
    pause
    exit /b 1
)

REM Check if PHP is installed
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo.
    echo ERROR: PHP is not installed or not in PATH
    echo.
    echo Please ensure PHP 8.0+ is installed and added to your system PATH
    echo.
    pause
    exit /b 1
)

echo.
echo ============================================
echo   Library Management System - Desktop App
echo ============================================
echo.

REM Check if node_modules exists
if not exist "node_modules" (
    echo Installing dependencies (first time only)...
    echo.
    call npm install
    if %errorlevel% neq 0 (
        echo.
        echo ERROR: Failed to install dependencies
        echo Please check your internet connection and try again
        pause
        exit /b 1
    )
    echo.
)

echo Starting application...
echo - PHP Server will start automatically
echo - Desktop window will open shortly
echo.
timeout /t 2

REM Start the application
call npm start

pause
