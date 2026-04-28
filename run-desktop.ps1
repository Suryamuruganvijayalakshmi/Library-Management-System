#!/usr/bin/env pwsh
# Library Management System - Desktop Application Launcher (PowerShell)

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Library Management System - Desktop App" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# Check if Node.js is installed
$nodeCheck = Get-Command node -ErrorAction SilentlyContinue
if (-not $nodeCheck) {
    Write-Host "ERROR: Node.js is not installed or not in PATH" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please download and install Node.js from: https://nodejs.org/" -ForegroundColor Yellow
    Write-Host "Make sure to check 'Add to PATH' during installation"
    Write-Host ""
    Read-Host "Press Enter to exit"
    exit 1
}

# Check if PHP is installed
$phpCheck = Get-Command php -ErrorAction SilentlyContinue
if (-not $phpCheck) {
    Write-Host "ERROR: PHP is not installed or not in PATH" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please ensure PHP 8.0+ is installed and added to your system PATH" -ForegroundColor Yellow
    Write-Host ""
    Read-Host "Press Enter to exit"
    exit 1
}

# Check if node_modules exists
if (-not (Test-Path "node_modules")) {
    Write-Host "Installing dependencies (first time only)..." -ForegroundColor Yellow
    Write-Host ""
    & npm install
    if ($LASTEXITCODE -ne 0) {
        Write-Host ""
        Write-Host "ERROR: Failed to install dependencies" -ForegroundColor Red
        Write-Host "Please check your internet connection and try again" -ForegroundColor Yellow
        Read-Host "Press Enter to exit"
        exit 1
    }
    Write-Host ""
}

Write-Host "Starting application..." -ForegroundColor Green
Write-Host "- PHP Server will start automatically"
Write-Host "- Desktop window will open shortly"
Write-Host ""

# Start the application
& npm start
