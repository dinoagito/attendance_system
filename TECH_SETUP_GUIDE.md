# Technician Setup Guide

This guide is for a school technician installing the Employee Attendance System on **one Windows computer**. It uses simple, sequential steps.

> Read this first: the exact production database version has not been certified. MariaDB 10.4.32 is the development baseline and is end-of-life. The current XAMPP and MariaDB 11.4 candidates still need testing. Read [DATABASE_COMPATIBILITY.md](DATABASE_COMPATIBILITY.md) before entering real attendance or visitor data.

## What This Guide Assumes

- One Windows 10 or Windows 11 computer, 64-bit.
- The computer is the web server, database server, and kiosk.
- The technician has administrator access.
- The ZK9500 scanner is optional for the first stage.
- The application is installed in `C:\xampp\htdocs\attendance_app`.
- PowerShell is used for commands.
- The computer is not exposed directly to the internet.

If the system is already working on this computer, do not reinstall it. Back up the database and files, then use this guide as a checklist. Do not replace a working database just to match a version number.

## What You Will Install

```text
Windows computer
├── XAMPP: Apache + PHP + MariaDB
├── Laravel application: C:\xampp\htdocs\attendance_app
├── Fingerprint server: fingerprint-server, port 3001
├── ZK9500 scanner (optional Stage 2)
└── Chrome or Edge kiosk browser
```

Laravel and the fingerprint server must use the same database.

## Before You Start

### Computer

Recommended for one attendance station:

| Item | Minimum | Recommended |
| --- | --- | --- |
| Windows | Windows 10 or 11, 64-bit | Windows 11, 64-bit |
| Processor | 4 cores | 6 cores or more |
| Memory | 8 GB | 16 GB |
| Free disk | 20 GB | 50 GB or more |
| Browser | Current Chrome or Edge | Current Chrome or Edge |
| Network | Needed for installation | Wired connection recommended |
| Scanner | Optional at first | ZKTeco ZK9500 with SDK |

Use a computer you can back up and restore. Do not install on a shared computer without the owner's permission.

### Files and installers

Gather these from official sources:

| Item | Purpose |
| --- | --- |
| XAMPP for Windows 64-bit | Apache, PHP, MariaDB, phpMyAdmin |
| Composer 2.x | PHP dependencies |
| Node.js 22.x and npm | Fingerprint server |
| Git for Windows | Optional, only if cloning the project |
| Chrome or Edge | Kiosk and admin browser |
| ZKTeco SDK and Visual C++ Redistributable | Only for a real ZK9500 scanner |
| Project source copy | The Laravel application |
| Database dump | Only if migrating existing records |

Official pages:

- XAMPP: <https://www.apachefriends.org/download.html>
- Composer: <https://getcomposer.org/download/>
- Node.js: <https://nodejs.org/en/download>
- Git: <https://git-scm.com/download/win>
- ZKTeco downloads: <https://www.zkteco.com/en/Downloads>
- Microsoft Visual C++ Redistributable: <https://learn.microsoft.com/en-us/cpp/windows/latest-supported-vc-redist>

### Database version decision

Do not assume the database version. XAMPP 8.3.36 currently lists MariaDB 10.11.16 on the official download page, but you must record the version actually installed on this computer.

Use this rule:

1. Install the current XAMPP candidate.
2. Record the actual MariaDB version.
3. Compare it with [DATABASE_COMPATIBILITY.md](DATABASE_COMPATIBILITY.md).
4. Do not enter real data until the compatibility tests pass.

MariaDB 10.4.32 is a development baseline only. Do not choose an old XAMPP 8.2.12 installation for a new school computer unless a documented risk decision has been made.

## Step 1: Prepare Windows

1. Install Windows updates.
2. Set the correct date, time, and timezone. For `Asia/Manila`, use UTC+8.
3. Turn off sleep and screen timeout while the kiosk is in use.
4. Create or confirm an administrator account for the technician.
5. Create a separate Windows account for daily kiosk use if possible.
6. Install Chrome or Edge.
7. Make sure the computer has enough free disk space.
8. Do not connect the computer directly to the public internet without a firewall.

## Step 2: Install XAMPP

1. Download the current XAMPP for Windows 64-bit installer from the official Apache Friends page. The documented first candidate is XAMPP 8.3.36.
2. Verify the installer checksum shown on the official page before running it.
3. Install XAMPP to `C:\xampp`.
4. Open **XAMPP Control Panel**.
5. Start **Apache**.
6. Start **MySQL** (the button may be labelled MySQL/MariaDB).
7. Confirm both services remain running.

If Apache or MySQL stops immediately, read the XAMPP log before continuing. Do not install a second copy over the first.

## Step 3: Record and Check the Database Version

Open PowerShell and run:

```powershell
& "C:\xampp\php\php.exe" -v
& "C:\xampp\mysql\bin\mysql.exe" --version
& "C:\xampp\mysql\bin\mysqld.exe" --version
```

If `mariadbd.exe` exists, also run:

```powershell
& "C:\xampp\mysql\bin\mariadbd.exe" --version
```

Write down the exact output in the Evidence Table in [DATABASE_COMPATIBILITY.md](DATABASE_COMPATIBILITY.md).

With MySQL/MariaDB running, check the row format:

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "SHOW VARIABLES LIKE 'innodb_default_row_format';"
```

The result should be `DYNAMIC`. If it is not, stop and follow the 1071 troubleshooting section before creating real data.

### Version gate

Do not enter real data if:

- The installed database version is not in a tested candidate listed in [DATABASE_COMPATIBILITY.md](DATABASE_COMPATIBILITY.md).
- The row format is not `DYNAMIC`.
- You cannot explain which database version will be used and whether it has been tested.

You may continue software-only testing on a disposable database, but do not import real records and do not treat the installation as production-ready until the compatibility tests pass.

## Step 4: Enable the Required PHP Settings

Open `C:\xampp\php\php.ini` in Notepad.

Confirm these extensions are enabled:

- `ctype`
- `fileinfo`
- `filter`
- `hash`
- `mbstring`
- `openssl`
- `session`
- `tokenizer`
- `PDO`
- `pdo_mysql`

Recommended:

- `curl`
- `exif`
- `xml`
- `zip`

Set at least these values for visitor photo uploads:

```ini
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 60
memory_limit = 256M
```

Save the file and restart Apache from the XAMPP Control Panel.

Check the extensions:

```powershell
& "C:\xampp\php\php.exe" -m
```

## Step 5: Put the Project in Place

The expected project path is:

```text
C:\xampp\htdocs\attendance_app
```

If the folder does not exist and you have Git:

```powershell
git clone https://github.com/dinoagito/attendance_system.git "C:\xampp\htdocs\attendance_app"
```

If the project is already present, do not clone over it. Change into the existing folder:

```powershell
Set-Location "C:\xampp\htdocs\attendance_app"
```

Confirm the folder contains `artisan`, `composer.json`, `public`, and `storage`.

## Step 6: Create the Database

### Easiest method: phpMyAdmin

1. Open `http://localhost/phpmyadmin`.
2. Select **Databases**.
3. Create a database named `attendance_app`.
4. Choose collation `utf8mb4_unicode_ci`.
5. Select **Create**.

### Command-line method

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS attendance_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

If your root account has a password, add `-p` and enter it when prompted.

### Local test account versus dedicated account

The XAMPP `root` account with a blank password is commonly used for a local test. It is not a good long-term account for real school data.

Before entering real data, create a dedicated database user with access only to `attendance_app`, update `.env`, and test the connection. The deployment guide has the security checklist: [DEPLOYMENT.md](DEPLOYMENT.md).

## Step 7: Create the Laravel Environment File

```powershell
Set-Location "C:\xampp\htdocs\attendance_app"
Copy-Item -LiteralPath ".env.example" -Destination ".env"
notepad .env
```

Set these values for a first local test:

```dotenv
APP_NAME="Employee Attendance System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=attendance_app
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Notes:

- The example `.env` uses SQLite by default. This project expects MySQL or MariaDB.
- Do not put spaces around `=`.
- Do not quote a blank password.
- Do not use a real production password in a screenshot or shared document.
- If you use MariaDB 11.4 on port `3307` during testing, set `DB_PORT=3307` here and in `fingerprint-server\.env`.
- If you use a different Laravel address, update `APP_URL` and the fingerprint server's `CORS_ORIGINS`.

## Step 8: Install PHP Dependencies and Generate the Key

```powershell
Set-Location "C:\xampp\htdocs\attendance_app"
composer install
php artisan key:generate
```

If PowerShell does not recognize `php` or `composer`, use full paths:

```powershell
& "C:\xampp\php\php.exe" artisan key:generate
& "C:\ProgramData\ComposerSetup\bin\composer.bat" install
```

The Composer path may differ. Locate it with:

```powershell
where.exe composer
```

Confirm Laravel can read the environment:

```powershell
php artisan about
```

## Step 9: Run the Database Migrations

For a new, empty database:

```powershell
Set-Location "C:\xampp\htdocs\attendance_app"
php artisan migrate
php artisan migrate:status
```

Every migration should show as applied.

Do not run `php artisan migrate:fresh` on a database that contains real attendance, visitor, employee, or fingerprint records. It deletes all tables and data.

If migration fails with error `1071`, stop and read the 1071 section in [INSTALLATION.md](INSTALLATION.md#troubleshooting-the-1071-key-length-error). Do not edit migrations just to hide the error.

## Step 10: Create the Storage Link and Clear Caches

```powershell
Set-Location "C:\xampp\htdocs\attendance_app"
php artisan storage:link
php artisan optimize:clear
```

Confirm the storage link exists:

```powershell
Test-Path "C:\xampp\htdocs\attendance_app\public\storage"
```

The result should be `True`.

## Step 11: Start and Test the Web Application

For the first test, use Laravel's local server:

```powershell
Set-Location "C:\xampp\htdocs\attendance_app"
php artisan serve --host=127.0.0.1 --port=8000
```

Open:

```text
http://localhost:8000
```

Stop the server with `Ctrl+C` when finished.

Test in this order:

- [ ] The login page loads.
- [ ] The registration page loads.
- [ ] A new account can be created and reaches the dashboard.
- [ ] The employee list loads.
- [ ] An employee can be created.
- [ ] A schedule can be assigned.
- [ ] Manual attendance can be added.
- [ ] Visitor registration works.
- [ ] The visitor log loads.
- [ ] Reports and print pages load.

Do not test the visitor CSV export yet. It has a known datetime formatting defect and will fail.

## Step 12: Create the First Account

1. Open `http://localhost:8000/register`.
2. Enter a name, password, and password confirmation.
3. Submit the form.
4. The system logs you in and opens the dashboard.
5. The account uses an internal generated email address. Login accepts the account name or that email.

There is no separate administrator role. Every registered account can manage all records. Because `/register` is public, restrict who can reach the computer or application.

## Step 13: Optional Permanent Apache Setup

Use this after the first test works. `php artisan serve` is for testing, not a permanent kiosk service.

1. Confirm `mod_rewrite` is enabled in `C:\xampp\apache\conf\httpd.conf`.
2. Open `C:\xampp\apache\conf\extra\httpd-vhosts.conf`.
3. Add a virtual host:

```apache
<VirtualHost *:80>
    ServerName attendance.local
    DocumentRoot "C:/xampp/htdocs/attendance_app/public"

    <Directory "C:/xampp/htdocs/attendance_app/public">
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog "logs/attendance.local-error.log"
    CustomLog "logs/attendance.local-access.log" combined
</VirtualHost>
```

4. Add this line to `C:\Windows\System32\drivers\etc\hosts` using an administrator editor:

```text
127.0.0.1 attendance.local
```

5. Change `APP_URL` in `.env` to `http://attendance.local`.
6. Add `http://attendance.local` to `CORS_ORIGINS` in `fingerprint-server\.env`.
7. Restart Apache.
8. Open `http://attendance.local`.

The Apache document root must point to the `public` folder, not the project root. Pointing it to the project root exposes `.env` and application files.

## Step 14: Optional Fingerprint Server and ZK9500

Complete this stage only after the web application works. A real scanner requires the official ZKTeco SDK and matching DLL architecture.

```powershell
Set-Location "C:\xampp\htdocs\attendance_app\fingerprint-server"
Copy-Item -LiteralPath ".env.example" -Destination ".env"
notepad .env
npm install
npm start
```

Set the fingerprint server's database values to match Laravel:

```dotenv
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USER=root
DB_PASSWORD=
DB_NAME=attendance_app
DB_SSL=false

PORT=3001
FINGERPRINT_SSL=false
CORS_ORIGINS=http://localhost:8000,http://127.0.0.1:8000
ZKTECO_SDK_PATH=C:/Program Files/ZKTeco/SDK/libzkfp.dll

TIME_IN_WINDOW_MINUTES=240
OVERTIME_MINUTES=60
UNDERTIME_MINUTES=60
```

`ZKTECO_SDK_PATH` must point to the DLL file, not just a folder. If the bundled DLL is used, point to:

```text
C:/xampp/htdocs/attendance_app/fingerprint-server/sdk/libzkfp.dll
```

Check health:

```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:3001/health" | ConvertTo-Json -Depth 5
```

A working scanner should report:

```text
scanner.initialized    : True
scanner.connected      : True
scanner.simulationMode : False
```

If `simulationMode` is `True`, the system cannot enroll or verify a real fingerprint. Do not use simulated captures as attendance records.

The detailed SDK and hardware steps are in [FINGERPRINT_SETUP.md](FINGERPRINT_SETUP.md).

## Step 15: Daily Startup Order

Start the components in this order:

1. MySQL/MariaDB.
2. Apache.
3. Fingerprint server (`npm start`).
4. Chrome or Edge.
5. Open `/kiosk` or `/scan/employee/zk9500`.

Then check:

```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:3001/health" | ConvertTo-Json -Depth 5
```

If the fingerprint server does not start automatically after a reboot, use Windows Task Scheduler or the documented startup method. PM2 is optional and is not configured by the project.

## Step 16: Back Up Before Real Data

Back up:

- The `attendance_app` database.
- Visitor photos in `storage\app\public\visitors`.
- The Laravel `.env` file.
- The fingerprint-server `.env` file.
- The source version or Git commit.

Use the database client that matches the installed version:

```powershell
& "C:\xampp\mysql\bin\mysqldump.exe" -u root --single-transaction --routines --triggers attendance_app --result-file="C:\xampp\htdocs\attendance_app\storage\backups\attendance_app_manual.sql"
```

If `mysqldump.exe` is not present, check for `mariadb-dump.exe` in the same folder. Do not assume the command name; verify it exists before relying on the backup.

Create the backup folder first if needed:

```powershell
New-Item -ItemType Directory -Force -Path "C:\xampp\htdocs\attendance_app\storage\backups"
```

Test the restore into a separate scratch database. Do not test a restore over live data.

More backup and restore commands: [COMMAND_REFERENCE.md](COMMAND_REFERENCE.md).

## Step 17: Troubleshooting

| Problem | What to check |
| --- | --- |
| PowerShell cannot find PHP | Use `& "C:\xampp\php\php.exe"` or add `C:\xampp\php` to PATH |
| Cannot connect to the database | Confirm MySQL/MariaDB is running; check `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`; confirm `pdo_mysql` is enabled |
| `1071` key-length error | Check `innodb_default_row_format`; see [INSTALLATION.md](INSTALLATION.md#troubleshooting-the-1071-key-length-error) |
| 404 under Apache | Point `DocumentRoot` and `<Directory>` to `...\attendance_app\public`; enable `mod_rewrite`; allow `.htaccess` |
| Visitor photos do not appear | Run `php artisan storage:link`; check `public\storage`; check `storage\app\public` permissions |
| Webcam does not open | Use `http://localhost`, `http://127.0.0.1`, or HTTPS; allow camera permission |
| Port 8000 is busy | Stop the other process or use another port |
| Port 3001 is busy | Stop the duplicate Node process; the frontend expects 3001 |
| Fingerprint health is `ok` but scanner is not real | Check `scanner.simulationMode`; install the SDK and DLL |
| Fingerprint server cannot reach the database | Confirm its `.env` matches Laravel and `/api/employees` works |
| Visitor CSV export fails | Known defect; do not use CSV export until source is corrected |

## Step 18: Known Limitations

- The exact production database target is not certified yet.
- MariaDB 10.4.32 is end-of-life and is a development baseline only.
- ZK9500 enrollment, matching, and attendance recording require real hardware verification.
- Windows Hello/WebAuthn enrollment UI is limited; do not assume it is a replacement for the ZK9500 flow.
- Visitor CSV export currently fails with a datetime formatting error.
- PM2 is not configured by the project; use `npm start`, Task Scheduler, or a documented startup method.
- There is no role separation. Every account can manage all records.
- Public registration is enabled and must be restricted.

## Step 19: Acceptance Checklist

Do not hand over the system for real use until these are true:

- [ ] Actual XAMPP, PHP, and MariaDB versions are recorded.
- [ ] The database version is an accepted candidate in [DATABASE_COMPATIBILITY.md](DATABASE_COMPATIBILITY.md).
- [ ] All migrations completed.
- [ ] The unique email index and JSON columns were created without errors.
- [ ] Login, registration, dashboard, employees, schedules, manual attendance, visitors, and reports work.
- [ ] A database backup was created and restored into a scratch database.
- [ ] Visitor photos are backed up.
- [ ] The system date, time, and timezone are correct.
- [ ] Public access to the kiosk and admin pages is controlled.
- [ ] Port `3001` is not exposed to the internet.
- [ ] Scanner health reports `simulationMode: false` before claiming biometric attendance works.
- [ ] A real ZK9500 enrollment and test scan were completed on this computer.
- [ ] The visitor CSV limitation is documented.
- [ ] The user knows how to start the system, back it up, and report a failure.

## Related Guides

- Database compatibility and test gates: [DATABASE_COMPATIBILITY.md](DATABASE_COMPATIBILITY.md)
- Quick commands: [TECHNICIAN_QUICK_REFERENCE.md](TECHNICIAN_QUICK_REFERENCE.md)
- Detailed installation: [INSTALLATION.md](INSTALLATION.md)
- ZK9500 setup: [FINGERPRINT_SETUP.md](FINGERPRINT_SETUP.md)
- Deployment and security: [DEPLOYMENT.md](DEPLOYMENT.md)
- Full command list: [COMMAND_REFERENCE.md](COMMAND_REFERENCE.md)
