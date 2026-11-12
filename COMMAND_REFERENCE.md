# Command Reference

This reference lists the commands used to install, run, verify, back up, and troubleshoot the Attendance Monitoring System on Windows PowerShell.

Run commands from the locations shown. Do not paste real passwords into shared documentation.

## 1. Path Variables

Set these once per PowerShell session:

```powershell
$AppRoot = "C:\xampp\htdocs\attendance_app"
$FingerprintRoot = Join-Path $AppRoot "fingerprint-server"
```

Change to the project root:

```powershell
Set-Location $AppRoot
```

Change to the fingerprint server:

```powershell
Set-Location $FingerprintRoot
```

## 2. Version and Prerequisite Checks

```powershell
php -v
composer --version
node -v
npm -v
git --version
```

Check PHP extensions:

```powershell
php -m
```

Check important PHP limits:

```powershell
php -r "echo 'upload_max_filesize=' . ini_get('upload_max_filesize') . PHP_EOL; echo 'post_max_size=' . ini_get('post_max_size') . PHP_EOL; echo 'max_execution_time=' . ini_get('max_execution_time') . PHP_EOL; echo 'memory_limit=' . ini_get('memory_limit') . PHP_EOL;"
```

Locate executables:

```powershell
Get-Command php
Get-Command composer
Get-Command node
Get-Command npm
```

## 3. First-Time Setup Commands

### Install Laravel Dependencies

```powershell
Set-Location $AppRoot
composer install
```

Production dependency install:

```powershell
composer install --no-dev --optimize-autoloader
```

If Composer runs out of memory:

```powershell
$env:COMPOSER_MEMORY_LIMIT = "-1"
composer install
```

### Create and Configure `.env`

```powershell
Set-Location $AppRoot
Copy-Item -LiteralPath ".env.example" -Destination ".env"
notepad .env
```

Generate the application key:

```powershell
php artisan key:generate
```

Check the application:

```powershell
php artisan about
```

### Create the Database

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS attendance_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Check the InnoDB row format:

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "SHOW VARIABLES LIKE 'innodb_default_row_format';"
```

### Run Migrations

```powershell
Set-Location $AppRoot
php artisan migrate
php artisan migrate:status
```

Do not run `php artisan migrate:fresh` on a database with real data.

Optional development seeder:

```powershell
php artisan db:seed
```

The seeder creates a known development test account. Do not use it as production administrator provisioning.

### Storage Link

```powershell
Set-Location $AppRoot
php artisan storage:link
```

Check the link:

```powershell
Test-Path "$AppRoot\public\storage"
```

### Clear Setup Caches

```powershell
Set-Location $AppRoot
php artisan optimize:clear
```

## 4. Start and Stop Laravel

### Start with the Development Server

```powershell
Set-Location $AppRoot
php artisan serve --host=127.0.0.1 --port=8000
```

Stop with `Ctrl+C`.

For local-network testing only:

```powershell
php artisan serve --host=0.0.0.0 --port=8000
```

This exposes the Laravel development server to the network. Use Apache for production.

### Open Laravel

```powershell
Start-Process "http://localhost:8000"
```

Health endpoint:

```powershell
Invoke-WebRequest -Uri "http://localhost:8000/up" -UseBasicParsing
```

### Check Laravel Routes

```powershell
Set-Location $AppRoot
php artisan route:list --except-vendor
```

### Stop a Process Listening on Port 8000

Inspect first:

```powershell
Get-NetTCPConnection -LocalPort 8000 -State Listen
```

Stop the owning process:

```powershell
$connection = Get-NetTCPConnection -LocalPort 8000 -State Listen
if ($connection) { Stop-Process -Id $connection.OwningProcess -Force }
```

Confirm the process belongs to Laravel or PHP before stopping it.

## 5. Fingerprint Server Commands

### Install Dependencies

```powershell
Set-Location $FingerprintRoot
npm install
```

A lockfile exists. For a deterministic install:

```powershell
Set-Location $FingerprintRoot
npm ci
```

### Configure the Fingerprint Server

```powershell
Set-Location $FingerprintRoot
Copy-Item -LiteralPath ".env.example" -Destination ".env"
notepad .env
```

The database settings in `fingerprint-server\.env` must match Laravel.

### Start Normally

```powershell
Set-Location $FingerprintRoot
npm start
```

`npm start` runs `watchdog.js`, which starts `server.js` and restarts it after an unexpected exit.

Stop with `Ctrl+C`.

### Run Without the Watchdog

```powershell
Set-Location $FingerprintRoot
npm run start:direct
```

### Development Mode

```powershell
Set-Location $FingerprintRoot
npm run dev
```

Development mode uses nodemon and restarts when source files change.

### Restart After `.env` Changes

1. Press `Ctrl+C` in the fingerprint server terminal.
2. Start it again:

```powershell
Set-Location $FingerprintRoot
npm start
```

## 6. Fingerprint Health Checks

### Scanner Health

```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:3001/health" |
    ConvertTo-Json -Depth 5
```

### Scanner Status Only

```powershell
(Invoke-RestMethod -Uri "http://127.0.0.1:3001/health").scanner |
    ConvertTo-Json -Depth 5
```

### API Status

```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:3001/api/status" |
    ConvertTo-Json -Depth 5
```

### Database-Backed Check

```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:3001/api/employees" |
    ConvertTo-Json -Depth 5
```

### Expected Fields

`/health` returns:

- `status`
- `scanner`
- `timestamp`

The scanner object contains:

- `initialized`
- `connected`
- `simulationMode`
- `deviceCount`
- `disconnectStrikeCount`
- `busy`
- `currentOperation`

A real scanner should report:

- `initialized: true`
- `connected: true`
- `simulationMode: false`

`simulationMode: true` means enrollment cannot work with a real finger. Health may still report `connected: true` in simulation mode.

`status: ok` only confirms that the HTTP server is responding. It does not prove that the scanner or database is working.

## 7. Port and Process Checks

### Test a Port

```powershell
Test-NetConnection -ComputerName 127.0.0.1 -Port 3001
Test-NetConnection -ComputerName 127.0.0.1 -Port 8000
Test-NetConnection -ComputerName 127.0.0.1 -Port 3306
```

### Find the Process Using a Port

```powershell
Get-NetTCPConnection -LocalPort 3001 -State Listen |
    Select-Object LocalAddress, LocalPort, OwningProcess

Get-Process -Id (Get-NetTCPConnection -LocalPort 3001 -State Listen).OwningProcess
```

### Stop the Fingerprint Server on Port 3001

Confirm the process is Node.js first:

```powershell
$connection = Get-NetTCPConnection -LocalPort 3001 -State Listen
if ($connection) {
    Get-Process -Id $connection.OwningProcess
}
```

Then stop it:

```powershell
$connection = Get-NetTCPConnection -LocalPort 3001 -State Listen
if ($connection) { Stop-Process -Id $connection.OwningProcess -Force }
```

### List Node Processes

```powershell
Get-Process node
```

### List Listening Ports

```powershell
Get-NetTCPConnection -State Listen |
    Sort-Object LocalPort |
    Select-Object LocalAddress, LocalPort, OwningProcess
```

## 8. Logs

### Laravel Log

```powershell
Set-Location $AppRoot
Get-Content -LiteralPath "storage\logs\laravel.log" -Tail 100
```

Follow the log live:

```powershell
Get-Content -LiteralPath "storage\logs\laravel.log" -Tail 50 -Wait
```

### Apache Logs

Typical XAMPP location:

```powershell
Get-ChildItem "C:\xampp\apache\logs"
Get-Content "C:\xampp\apache\logs\error.log" -Tail 100
```

### Fingerprint Server Output

The default `npm start` writes to the PowerShell window that launched it. Keep that window visible or redirect output to a log file if you need persistent records.

### Clear the Laravel Log

Only do this after reviewing or archiving the log:

```powershell
Clear-Content -LiteralPath "storage\logs\laravel.log"
```

Do not clear logs while diagnosing an active failure.

## 9. Laravel Cache and Maintenance Commands

### Clear Everything

```powershell
Set-Location $AppRoot
php artisan optimize:clear
```

### Individual Clear Commands

```powershell
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### Rebuild Configuration and Views

```powershell
php artisan config:cache
php artisan view:cache
```

Run `config:clear` before editing `.env`, then run `config:cache` again.

Do not use `route:cache` unless you have reviewed the closure routes in `routes/web.php`. Route caching can fail when routes are defined with closures.

### Maintenance Mode

```powershell
Set-Location $AppRoot
php artisan down
```

Exit maintenance mode:

```powershell
Set-Location $AppRoot
php artisan up
```

### Inspect Migration State

```powershell
php artisan migrate:status
php artisan about
```

### Optional Developer Test Command

```powershell
php artisan test
```

The repository currently contains framework example tests. Do not assume they represent full application coverage.

## 10. Database Commands

### Open the MySQL Client

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -p
```

If the local root account has no password, omit `-p`.

### Show Databases

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "SHOW DATABASES;"
```

### Show Tables

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "SHOW TABLES;" attendance_app
```

### Show the Users Table

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "SELECT id, name, email, created_at FROM users;" attendance_app
```

Do not print password hashes or secrets in shared logs.

### Check Row Format

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "SHOW VARIABLES LIKE 'innodb_default_row_format';"
```

### Repair the 1071 Key-Length Error on an Empty Development Database

Check the row format first. If it is not `DYNAMIC`, update `C:\xampp\mysql\bin\my.ini`:

```ini
[mysqld]
innodb_default_row_format=DYNAMIC
```

Restart MySQL. Then recreate the empty database and rerun migrations:

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "DROP DATABASE IF EXISTS attendance_app; CREATE DATABASE attendance_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
Set-Location $AppRoot
php artisan migrate
```

Do not drop a database with real attendance or visitor records.

## 11. Database Backup and Restore

### Create a Backup Directory

```powershell
New-Item -ItemType Directory -Force -Path "$AppRoot\storage\backups"
```

### Back Up the Database

Use `--result-file` rather than PowerShell `>` redirection:

```powershell
& "C:\xampp\mysql\bin\mysqldump.exe" -u root --single-transaction --routines --triggers attendance_app --result-file="$AppRoot\storage\backups\attendance_app_2026-11-12.sql"
```

If the account has a password, add `-p` and enter it when prompted. Do not place the password on the command line.

### Verify the Backup File

```powershell
Get-Item "$AppRoot\storage\backups\attendance_app_2026-11-12.sql" |
    Select-Object FullName, Length, LastWriteTime
```

### Restore the Database

In PowerShell, use `cmd /c` for input redirection:

```powershell
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root -p attendance_app < C:\xampp\htdocs\attendance_app\storage\backups\attendance_app_2026-11-12.sql"
```

Back up the current database before restoring.

### Back Up Visitor Photos

```powershell
Compress-Archive -Path "$AppRoot\storage\app\public\visitors" -DestinationPath "D:\Backups\attendance_visitors.zip" -Force
```

Backups contain personal information. Store them securely and restrict access.

## 12. Optional PM2 Commands for Windows

PM2 is **not installed or configured by this repository**. `npm start` with `watchdog.js` is the default process method.

Use PM2 only as an optional Windows administration choice.

### Install PM2

```powershell
npm install -g pm2
```

### Start the Fingerprint Server Under PM2

Run PM2 from the fingerprint-server directory:

```powershell
Set-Location $FingerprintRoot
pm2 start server.js --name attendance-fingerprint
```

PM2 then provides restart handling. Do not also run the project watchdog for the same process.

If you prefer the watchdog under PM2:

```powershell
Set-Location $FingerprintRoot
pm2 start watchdog.js --name attendance-fingerprint-watchdog
```

Choose one method only.

### PM2 Status and Logs

```powershell
pm2 status
pm2 logs attendance-fingerprint
pm2 logs attendance-fingerprint --lines 100
```

### PM2 Restart, Stop, and Delete

```powershell
pm2 restart attendance-fingerprint
pm2 stop attendance-fingerprint
pm2 delete attendance-fingerprint
```

### PM2 Save and Restore

```powershell
pm2 save
pm2 resurrect
```

On Windows, PM2 does not provide native boot integration equivalent to Linux `systemd`. Use Windows Task Scheduler to run the server at boot, or run `pm2 resurrect` from a startup task.

Do not use Linux `systemd` commands or assume `pm2 startup` configures Windows services.

## 13. Windows Task Scheduler Alternative

Use Task Scheduler if you do not want PM2.

Create a task with:

- **Program/script:** `C:\Program Files\nodejs\npm.cmd`
- **Arguments:** `start`
- **Start in:** `C:\xampp\htdocs\attendance_app\fingerprint-server`
- **Trigger:** at system startup or kiosk user logon
- **Account:** an account that can access the scanner and project directory

Test by rebooting Windows and checking:

```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:3001/health" |
    ConvertTo-Json -Depth 5
```

## 14. Daily Operations

### Start of Day

1. Confirm MySQL/MariaDB is running.
2. Confirm Apache is running.
3. Confirm the fingerprint server is running.
4. Check scanner health.
5. Open `/kiosk` on the kiosk browser.
6. Confirm the system date and time are correct.

```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:3001/health" |
    ConvertTo-Json -Depth 5
```

### During Operation

- Do not stop MySQL or the fingerprint server while attendance is being recorded.
- Do not leave simulation mode enabled for live attendance.
- Keep the scanner USB connected.
- Monitor the kiosk for connection errors.
- Keep the PC timezone correct.

### End of Day

- Review the dashboard and attendance report.
- Check for scanners or servers that stopped unexpectedly.
- Confirm backups run according to your policy.
- Leave services running if the kiosk operates continuously.

## 15. Troubleshooting Commands

### Laravel Shows an Error

```powershell
Set-Location $AppRoot
php artisan about
php artisan config:clear
Get-Content -LiteralPath "storage\logs\laravel.log" -Tail 100
```

### 404 or Route Failures

```powershell
Set-Location $AppRoot
php artisan route:list --except-vendor
Get-ChildItem "C:\xampp\apache\logs"
```

Check that Apache points to:

```text
C:\xampp\htdocs\attendance_app\public
```

### Database Connection Failure

```powershell
Test-NetConnection -ComputerName 127.0.0.1 -Port 3306
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "SHOW DATABASES;"
Set-Location $AppRoot
php artisan config:clear
```

### Migration Failure

```powershell
Set-Location $AppRoot
php artisan migrate:status
Get-Content -LiteralPath "storage\logs\laravel.log" -Tail 100
```

Check `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`.

### Storage or Photo Upload Failure

```powershell
Set-Location $AppRoot
php artisan storage:link
Test-Path "$AppRoot\public\storage"
Get-ChildItem "$AppRoot\storage\app\public\visitors"
php -m | Select-String -Pattern "fileinfo"
```

### Fingerprint Server Does Not Start

```powershell
Set-Location $FingerprintRoot
node -v
npm -v
npm ls --depth=0
Get-NetTCPConnection -LocalPort 3001 -State Listen
```

### Scanner Not Detected

```powershell
Get-PnpDevice | Where-Object { $_.FriendlyName -match "ZK|Fingerprint" }
Get-Process node
Test-Path "$AppRoot\fingerprint-server\sdk\libzkfp.dll"
```

Open Device Manager:

```powershell
Start-Process devmgmt.msc
```

### Check the SDK Path

```powershell
Get-ChildItem "C:\Program Files\ZKTeco" -Recurse -Filter "libzkfp.dll" -ErrorAction SilentlyContinue |
    Select-Object FullName, Length, LastWriteTime
```

Set `ZKTECO_SDK_PATH` to the full DLL file path, not a directory, then restart the fingerprint server.

### Health Shows Simulation Mode

```powershell
(Invoke-RestMethod -Uri "http://127.0.0.1:3001/health").scanner |
    ConvertTo-Json -Depth 5
```

If `simulationMode` is `true`:

- Confirm the DLL loads.
- Confirm the SDK and Visual C++ runtime are installed.
- Confirm the scanner appears in Device Manager.
- Restart the fingerprint server.

### CORS Failure

Check the browser origin and the configured origins:

```powershell
Get-Content -LiteralPath "$FingerprintRoot\.env" |
    Select-String -Pattern "CORS_ORIGINS"
```

The scheme, host, and port must match exactly. Restart the server after changing `.env`.

### Check the Laravel and Fingerprint Database Are the Same

```powershell
Get-Content -LiteralPath "$AppRoot\.env" |
    Select-String -Pattern "DB_CONNECTION|DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME"

Get-Content -LiteralPath "$FingerprintRoot\.env" |
    Select-String -Pattern "DB_HOST|DB_PORT|DB_NAME|DB_USER"
```

Do not print full `.env` contents to shared screenshots or logs.

### Node Engine Warning

```powershell
node -v
npm -v
```

The root Vite toolchain requires Node `^20.19.0` or `>=22.12.0`. Update Node if you need root frontend development commands.

### Check Root npm Packages

```powershell
Set-Location $AppRoot
npm ls --depth=0
```

### Check Fingerprint npm Packages

```powershell
Set-Location $FingerprintRoot
npm ls --depth=0
```

## 16. Safe Command Warnings

Avoid these on a live system unless you have a verified backup and a clear recovery plan:

- `php artisan migrate:fresh`
- `DROP DATABASE attendance_app`
- `Stop-Process` against an unknown process
- `Remove-Item` against `storage`, uploaded visitor photos, or backups
- `pm2 delete` when you still need the process configuration
- Any command containing a real password in shell history

## 17. Quick Command Summary

```powershell
# Project paths
$AppRoot = "C:\xampp\htdocs\attendance_app"
$FingerprintRoot = Join-Path $AppRoot "fingerprint-server"

# Laravel setup
Set-Location $AppRoot
composer install
php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan optimize:clear

# Start Laravel
php artisan serve --host=127.0.0.1 --port=8000

# Start fingerprint server
Set-Location $FingerprintRoot
npm install
npm start

# Health
Invoke-RestMethod -Uri "http://127.0.0.1:3001/health" | ConvertTo-Json -Depth 5
Invoke-WebRequest -Uri "http://localhost:8000/up" -UseBasicParsing

# Logs
Set-Location $AppRoot
Get-Content -LiteralPath "storage\logs\laravel.log" -Tail 100

# Ports
Get-NetTCPConnection -LocalPort 3001 -State Listen
Get-NetTCPConnection -LocalPort 8000 -State Listen
```
