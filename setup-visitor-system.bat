@echo off
REM Visitor Registration System - Quick Setup Script
REM This script sets up the visitor registration feature

echo.
echo ====================================
echo Visitor Registration System Setup
echo ====================================
echo.

cd C:\xampp\htdocs\attendance_app

echo [1/5] Checking PHP version...
php -v | find "PHP"

echo.
echo [2/5] Running database migrations...
php artisan migrate --force

echo.
echo [3/5] Creating storage link...
php artisan storage:link

echo.
echo [4/5] Clearing application cache...
php artisan cache:clear
php artisan config:clear

echo.
echo [5/5] Done!
echo.
echo ====================================
echo Setup Complete!
echo ====================================
echo.
echo To start using the system:
echo 1. Start your web server (if not already running)
echo 2. Navigate to: http://localhost:8000/visitor/register
echo 3. Add some employees first (if not done)
echo 4. Register a visitor with photo
echo.
echo Documentation:
echo - VISITOR_SYSTEM_SUMMARY.md - Quick overview
echo - VISITOR_REGISTRATION_GUIDE.md - Detailed guide
echo.
pause
