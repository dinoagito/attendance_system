@echo off
title ZKTeco SDK Setup Helper
color 0A

echo =====================================================
echo   ZKTeco ZK9500 Fingerprint Scanner Setup
echo =====================================================
echo.

echo Your ZK9500 needs the ZKTeco SDK to work directly.
echo.
echo OPTION 1: Use Windows Hello (Recommended)
echo   - Go to Windows Settings ^> Accounts ^> Sign-in options
echo   - Set up Windows Hello Fingerprint using your ZK9500
echo   - The attendance system will use Windows Hello automatically
echo.

echo OPTION 2: Download ZKTeco SDK
echo   1. Go to: https://www.zkteco.com/en/Downloads
echo   2. Search for "ZKFinger SDK" or "libzkfp"
echo   3. Download and install the SDK
echo   4. Copy libzkfp.dll to:
echo      - C:\Windows\System32\
echo      - OR %~dp0fingerprint-server\sdk\
echo.

echo OPTION 3: Check Device Driver
echo   - Open Device Manager
echo   - Find ZK9500 under Biometric devices
echo   - If showing error, update driver
echo   - Try installing from ZKTeco website
echo.

echo Press any key to open ZKTeco Downloads page...
pause > nul
start https://www.zkteco.com/en/Downloads

echo.
echo After installing SDK, restart the fingerprint server:
echo   cd fingerprint-server
echo   npm start
echo.
pause
