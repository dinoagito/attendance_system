# Technician Quick Reference

Keep this page with the attendance computer. It uses PowerShell and assumes the project is at `C:\xampp\htdocs\attendance_app`.

> The database target is not certified until the tests in [DATABASE_COMPATIBILITY.md](DATABASE_COMPATIBILITY.md) pass. Do not use real data on an untested database version.

## Paths

```powershell
$AppRoot = "C:\xampp\htdocs\attendance_app"
$FingerprintRoot = Join-Path $AppRoot "fingerprint-server"
$BackupRoot = Join-Path $AppRoot "storage\backups"
```

## First-Time Order

1. Install XAMPP and start Apache and MySQL/MariaDB.
2. Record PHP and database versions.
3. Put the project in `C:\xampp\htdocs\attendance_app`.
4. Create the `attendance_app` database.
5. Configure `.env`.
6. Run `composer install`.
7. Run `php artisan key:generate`.
8. Run `php artisan migrate`.
9. Run `php artisan storage:link`.
10. Start and test the web application.
11. Register the first account.
12. Configure the fingerprint server only after the web application works.

Full walkthrough: [TECHNICIAN_SETUP_GUIDE.md](TECHNICIAN_SETUP_GUIDE.md).

## Version Checks

```powershell
& "C:\xampp\php\php.exe" -v
& "C:\xampp\mysql\bin\mysql.exe" --version
& "C:\xampp\mysql\bin\mysqld.exe" --version
```

If present:

```powershell
& "C:\xampp\mysql\bin\mariadbd.exe" --version
```

Record the exact database version in [DATABASE_COMPATIBILITY.md](DATABASE_COMPATIBILITY.md). Do not assume the version from the XAMPP download page.

With the database running:

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "SELECT VERSION(), @@version_comment;"
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "SHOW VARIABLES LIKE 'innodb_default_row_format';"
```

Expected row format: `DYNAMIC`.

## Database and Application Commands

```powershell
Set-Location $AppRoot

# Install dependencies
composer install

# Generate the application key
php artisan key:generate

# Create the tables
php artisan migrate
php artisan migrate:status

# Visitor photo link
php artisan storage:link

# Clear caches after changing .env
php artisan optimize:clear

# Check the application
php artisan about
```

Do not run `php artisan migrate:fresh` on a database with real records.

## Start the Web Application

For testing:

```powershell
Set-Location $AppRoot
php artisan serve --host=127.0.0.1 --port=8000
```

Open:

```text
http://localhost:8000
```

Stop with `Ctrl+C`.

For a permanent kiosk, use Apache with the document root set to:

```text
C:\xampp\htdocs\attendance_app\public
```

Do not point Apache at the project root.

## Start the Fingerprint Server

```powershell
Set-Location $FingerprintRoot
npm install
npm start
```

`npm start` runs `watchdog.js`, which restarts `server.js` if it exits.

Stop with `Ctrl+C`.

Other options:

```powershell
npm run start:direct
npm run dev
```

## Health Checks

```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:3001/health" |
    ConvertTo-Json -Depth 5
```

```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:3001/api/status" |
    ConvertTo-Json -Depth 5
```

Database-backed check:

```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:3001/api/employees" |
    ConvertTo-Json -Depth 5
```

Laravel health:

```powershell
Invoke-WebRequest -Uri "http://localhost:8000/up" -UseBasicParsing
```

A real scanner should report:

```text
scanner.initialized    : True
scanner.connected      : True
scanner.simulationMode : False
```

`status: ok` only means the HTTP server is responding. It does not prove the scanner or database is ready.

## Daily Startup Order

1. MySQL/MariaDB.
2. Apache.
3. Fingerprint server.
4. Chrome or Edge.
5. `/kiosk` or `/scan/employee/zk9500`.

Check the date, time, timezone, scanner health, and database connection.

## Back Up Before Changes

```powershell
New-Item -ItemType Directory -Force -Path $BackupRoot
```

Use the database client that exists on this computer:

```powershell
& "C:\xampp\mysql\bin\mysqldump.exe" -u root --single-transaction --routines --triggers attendance_app --result-file="$BackupRoot\attendance_app_manual.sql"
```

If `mysqldump.exe` is missing, check for:

```powershell
& "C:\xampp\mysql\bin\mariadb-dump.exe" -u root --single-transaction --routines --triggers attendance_app --result-file="$BackupRoot\attendance_app_manual.sql"
```

Do not assume the command name. Verify the executable exists before relying on the backup.

Back up visitor photos:

```powershell
Compress-Archive -Path "$AppRoot\storage\app\public\visitors" -DestinationPath "$BackupRoot\visitors.zip" -Force
```

Back up `.env` and `fingerprint-server\.env` separately in a protected location.

## Restore a Backup

Back up the current database first. Stop writes to the application, then use:

```powershell
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root -p attendance_app < C:\xampp\htdocs\attendance_app\storage\backups\attendance_app_manual.sql"
```

If the installed client is MariaDB 11.4, check whether `mysql.exe` or `mariadb.exe` is the correct client. Test the restore on a scratch database before restoring over live data.

## Logs

```powershell
Set-Location $AppRoot
Get-Content -LiteralPath "storage\logs\laravel.log" -Tail 100
```

```powershell
Get-Content "C:\xampp\apache\logs\error.log" -Tail 100
```

Database error logs are in the XAMPP MySQL data directory. Do not edit them while diagnosing a failure.

## Port Checks

```powershell
Get-NetTCPConnection -LocalPort 3001 -State Listen
Get-NetTCPConnection -LocalPort 8000 -State Listen
Get-NetTCPConnection -LocalPort 3306 -State Listen
```

Find the process using a port:

```powershell
Get-NetTCPConnection -LocalPort 3001 -State Listen |
    Select-Object LocalAddress, LocalPort, OwningProcess

Get-Process -Id (Get-NetTCPConnection -LocalPort 3001 -State Listen).OwningProcess
```

Confirm a process before stopping it.

## Common Problems

| Problem | First check |
| --- | --- |
| `php` is not recognized | Use `& "C:\xampp\php\php.exe"` or fix PATH |
| Database connection refused | Is MySQL/MariaDB running? Check `.env` host, port, database, user, and password |
| `1071` key-length error | Check `innodb_default_row_format`; read the installation guide |
| 404 under Apache | Apache document root must be `...\attendance_app\public`; enable `mod_rewrite` |
| Visitor photo missing | Run `php artisan storage:link`; check `public\storage` and permissions |
| Webcam blocked | Use `localhost`, `127.0.0.1`, or HTTPS; allow camera access |
| Fingerprint health `ok` but scanner not real | Check `scanner.simulationMode`; fix SDK/DLL loading |
| Port `3001` in use | Stop the duplicate Node process; the frontend expects 3001 |
| CSV export fails | Known datetime formatting defect; do not use CSV export yet |
| Apache starts but Laravel returns 500 | Check `storage\logs\laravel.log`, `.env`, and `php artisan about` |

## Safety Rules

- Do not expose port `3001` to the internet.
- Do not expose the database port to the network.
- Do not use the XAMPP `root` account for real data if a dedicated account can be created.
- Do not use public registration without restricting who can reach the application.
- Do not restore a backup over live data without backing up the live database first.
- Do not run `migrate:fresh`, `DROP DATABASE`, or delete `storage` on a live system.
- Do not use simulation-mode fingerprint captures as attendance.
- Do not claim biometric attendance works until a real ZK9500 enrollment and test scan pass.
- Do not upgrade the database version without reading [DATABASE_COMPATIBILITY.md](DATABASE_COMPATIBILITY.md) and testing.

## Related Guides

- Full setup walkthrough: [TECHNICIAN_SETUP_GUIDE.md](TECHNICIAN_SETUP_GUIDE.md)
- Database version status and test gates: [DATABASE_COMPATIBILITY.md](DATABASE_COMPATIBILITY.md)
- Detailed installation: [INSTALLATION.md](INSTALLATION.md)
- ZK9500 setup: [FINGERPRINT_SETUP.md](FINGERPRINT_SETUP.md)
- Permanent deployment: [DEPLOYMENT.md](DEPLOYMENT.md)
- Full command list: [COMMAND_REFERENCE.md](COMMAND_REFERENCE.md)
