# Installation Guide

This guide installs the Laravel web application on a Windows PC using XAMPP. It covers the software-only installation. The ZK9500 scanner is configured separately in [FINGERPRINT_SETUP.md](FINGERPRINT_SETUP.md).

For a beginner-friendly, one-computer walkthrough, start with [TECHNICIAN_SETUP_GUIDE.md](TECHNICIAN_SETUP_GUIDE.md). This document is the detailed reference.

The commands below use PowerShell. Run them from the project root unless a step says otherwise.

> Important: Stage 1 is complete for testing when the web interface and database work. It is not production-certified until the database compatibility tests pass. Biometric attendance is Stage 2 and requires the fingerprint server, ZKFinger SDK, and a working ZK9500 scanner.

> Database notice: MariaDB 10.4.32 is the verified development baseline but is end-of-life. The deployment database target is not certified yet. Read [DATABASE_COMPATIBILITY.md](DATABASE_COMPATIBILITY.md) and complete its test gates before storing real attendance or visitor data.

## 1. Installation Choices

Two common layouts are supported:

1. **All-in-one kiosk PC**: Laravel, MySQL/MariaDB, the fingerprint server, and the ZK9500 are on one Windows PC. This is the simplest and recommended layout for a single attendance station.
2. **Central Laravel server plus kiosk PC**: Laravel and the database run on a server, while the kiosk PC runs the fingerprint server locally for its USB scanner. This requires network, HTTPS, and CORS planning. See [DEPLOYMENT.md](DEPLOYMENT.md).

This installation guide assumes the all-in-one layout.

## 2. Software Requirements

| Software | Requirement |
| --- | --- |
| Windows | Windows 10 or Windows 11, 64-bit recommended |
| XAMPP | Includes Apache, PHP, MySQL/MariaDB, and phpMyAdmin |
| PHP | 8.2 or newer |
| Composer | 2.x |
| Git | Required to clone the repository |
| Node.js | Required for the fingerprint server; Node 22.12+ or 20.19+ recommended |
| Browser | Current Chrome or Edge |
| ZKTeco SDK | Required only for a real ZK9500 scanner |

Official download pages:

- XAMPP: https://www.apachefriends.org/download.html
- Composer: https://getcomposer.org/download/
- Git for Windows: https://git-scm.com/download/win
- Node.js: https://nodejs.org/en/download
- Laravel documentation: https://laravel.com/docs/12.x
- ZKTeco downloads: https://www.zkteco.com/en/Downloads
- Microsoft Visual C++ Redistributable: https://learn.microsoft.com/en-us/cpp/windows/latest-supported-vc-redist

### Database Version Gate

Do not assume that a new XAMPP installer contains a particular MariaDB version. The official Apache Friends page currently lists XAMPP 8.3.36 with MariaDB 10.11.16, but that is package metadata. Record the actual installed version with:

```powershell
& "C:\xampp\mysql\bin\mysql.exe" --version
& "C:\xampp\mysql\bin\mysqld.exe" --version
```

If present, also run `mariadbd.exe --version`. Use the measured version in the C1 candidate label.

The documented candidates are:

- **C1:** XAMPP 8.3.36 with its actual installed MariaDB version, expected to be 10.11.x.
- **C2:** MariaDB 11.4.13 standalone with XAMPP used only for Apache/PHP.

Neither candidate is production-ready until the full test matrix in [DATABASE_COMPATIBILITY.md](DATABASE_COMPATIBILITY.md) passes. MariaDB 10.4.32 remains a development baseline only and is not recommended for a new school computer.

### Node.js Is Required in Two Different Ways

- **Laravel interface:** Node.js is optional. The current Blade layouts load committed files from `public/vendor` and do not use Vite.
- **Fingerprint server:** Node.js is required. The `fingerprint-server` directory is a separate Node.js application that must be installed and started independently.

Use a Node.js release compatible with the root Vite toolchain if you also plan to modify frontend assets. Vite 7.3.2 requires Node `^20.19.0` or `>=22.12.0`.

## 3. Required PHP Configuration

Open `C:\xampp\php\php.ini` and confirm these extensions are enabled:

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

Recommended additional extensions:

- `curl`
- `exif`
- `xml`
- `zip`

Check the available extensions:

```powershell
php -m
```

Visitor photos can be up to 5 MB. Set these values to at least 10 MB in `php.ini`:

```ini
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 60
memory_limit = 256M
```

Restart Apache after changing `php.ini`.

If PowerShell cannot find `php`, either add `C:\xampp\php` to `PATH` or call the executable directly:

```powershell
& "C:\xampp\php\php.exe" -v
```

## 4. Clone the Project

Open the XAMPP Shell or PowerShell.

If `C:\xampp\htdocs\attendance_app` does not exist:

```powershell
git clone https://github.com/dinoagito/attendance_system.git "C:\xampp\htdocs\attendance_app"
```

If the project is already present, do not clone over it. Change into the existing directory:

```powershell
Set-Location "C:\xampp\htdocs\attendance_app"
```

Confirm the remote:

```powershell
git remote -v
```

The expected repository is:

```text
https://github.com/dinoagito/attendance_system
```

## 5. Start XAMPP Services

Open the XAMPP Control Panel and start:

- Apache
- MySQL

Confirm PHP, Composer, and the database:

```powershell
php -v
composer --version
& "C:\xampp\mysql\bin\mysql.exe" --version
& "C:\xampp\mysql\bin\mysqld.exe" --version
```

The repository inspection was performed on PHP 8.2.12 and Composer 2.8.1. XAMPP 8.3.36 uses PHP 8.3.36, which has not been tested with this application. Record the actual PHP and MariaDB versions before continuing.

## 6. Create the Database

### Option A: phpMyAdmin

1. Open `http://localhost/phpmyadmin`.
2. Select **Databases**.
3. Create a database named `attendance_app`.
4. Choose collation `utf8mb4_unicode_ci`.

### Option B: MySQL Command Line

XAMPP's MySQL executable is normally at `C:\xampp\mysql\bin\mysql.exe`.

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS attendance_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

If your local root account has a password, add `-p` and enter it when prompted.

Depending on the installed MariaDB version, the client may also be available as `C:\xampp\mysql\bin\mariadb.exe`. Verify which client exists before relying on a command name.

For a real school deployment, use a dedicated database account with access only to `attendance_app` instead of the XAMPP `root` account. The initial local test can use root, but change it before entering real data. See the security checklist in [DEPLOYMENT.md](DEPLOYMENT.md).

### Check the InnoDB Row Format

Modern MariaDB versions normally use `DYNAMIC` row format. Check it before migration:

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "SHOW VARIABLES LIKE 'innodb_default_row_format';"
```

If the value is not `DYNAMIC`, see [Troubleshooting the 1071 key-length error](#troubleshooting-the-1071-key-length-error) before running migrations.

## 7. Create the Laravel Environment File

Copy the example file:

```powershell
Set-Location "C:\xampp\htdocs\attendance_app"
Copy-Item -LiteralPath ".env.example" -Destination ".env"
```

Open `.env` in Notepad:

```powershell
notepad .env
```

Set at least these values. Replace example credentials with your own.

```dotenv
APP_NAME="Attendance Monitoring System"
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

- With the default XAMPP account, `DB_USERNAME` is `root` and `DB_PASSWORD` is often blank.
- Do not put spaces around the `=` sign.
- Do not quote a blank password.
- Do not copy a real production password into documentation or version control.
- If you use an Apache virtual host, change `APP_URL` to that address, for example `http://attendance.local`.
- `APP_KEY` will be generated in the next step. Do not invent one manually.

The default `.env.example` uses SQLite. This project expects MySQL or MariaDB, so the database settings must be changed as shown above.

## 8. Install PHP Dependencies

```powershell
Set-Location "C:\xampp\htdocs\attendance_app"
composer install
```

If Composer runs out of memory:

```powershell
$env:COMPOSER_MEMORY_LIMIT = "-1"
composer install
```

Generate the application key:

```powershell
php artisan key:generate
```

Confirm Laravel can read the environment:

```powershell
php artisan about
```

## 9. Run the Database Migrations

For a new database:

```powershell
php artisan migrate
```

Check migration status:

```powershell
php artisan migrate:status
```

If the command asks whether to create the database, confirm that `DB_DATABASE=attendance_app` is correct before answering.

Do not use `php artisan migrate:fresh` on a database that already contains employees, attendance, visitors, or fingerprints. It deletes all tables and data.

The migration commands are documented from the existing project. They have not been verified against XAMPP 8.3.36's installed MariaDB version or MariaDB 11.4. Record the result in [DATABASE_COMPATIBILITY.md](DATABASE_COMPATIBILITY.md) before using the database for real records.

## 10. Create the Public Storage Link

Visitor photos are stored on Laravel's `public` disk.

```powershell
php artisan storage:link
```

The expected result is `public\storage` pointing to `storage\app\public`.

If the link already exists, `storage:link` reports that it exists. That is normally safe. Do not delete `storage\app\public` contents.

Ensure the account running Apache or PHP can write to:

- `storage`
- `bootstrap\cache`
- `public\storage`

Avoid granting broad access to `Everyone`. Use the actual service or user account that runs Apache.

## 11. Clear Setup Caches

```powershell
php artisan optimize:clear
```

This clears configuration, route, view, and application caches. Run it after changing `.env` or deployment settings.

## 12. Create the First Account

The application does not ship with a production administrator account.

The verified account-creation process is:

1. Open `http://localhost:8000/register`.
2. Enter a name, password, and password confirmation.
3. Submit the form.
4. The application creates the user, logs in automatically, and redirects to the dashboard.
5. The account is stored with an internal generated email address such as `<name-slug>-<timestamp>@local.test`.
6. Login later accepts either the account name or that generated email.

There is no separate administrator role. Every registered user can access all authenticated routes, including employee, schedule, attendance, visitor, and fingerprint management.

### Optional Development Seeder

`php artisan db:seed` runs `DatabaseSeeder`. That seeder creates a development test account through the framework's user factory using its known test password. It is not a production account-provisioning process. Do not use it on a production kiosk, and change or remove any seeded test account.

Because `/register` is public, restrict who can reach the application. See the security checklist in [DEPLOYMENT.md](DEPLOYMENT.md).

## 13. Start the Laravel Application

### Option A: Laravel Development Server

This is the easiest option for local testing:

```powershell
Set-Location "C:\xampp\htdocs\attendance_app"
php artisan serve --host=127.0.0.1 --port=8000
```

Open:

```text
http://localhost:8000
```

Stop the server with `Ctrl+C`.

`php artisan serve` is intended for development and simple local testing. Use Apache for a permanent deployment.

### Option B: Apache Virtual Host

Use this for a kiosk or production-style installation.

1. Enable `mod_rewrite` in Apache. In XAMPP, the module is normally enabled in `C:\xampp\apache\conf\httpd.conf`.
2. Open `C:\xampp\apache\conf\extra\httpd-vhosts.conf`.
3. Make sure the virtual-hosts file is included in `httpd.conf`.
4. Add a virtual host:

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

5. Add this line to `C:\Windows\System32\drivers\etc\hosts` using an administrator editor:

```text
127.0.0.1 attendance.local
```

6. Restart Apache.
7. Open `http://attendance.local`.

The Apache `DocumentRoot` must point to the `public` directory, not the project root. Pointing it to `C:\xampp\htdocs\attendance_app` exposes application files and breaks Laravel routing.

If you use a virtual host, update both:

- `APP_URL` in `.env`
- `CORS_ORIGINS` in `fingerprint-server\.env`

## 14. Optional: Install Root Frontend Tooling

This is not required to run the current Laravel interface.

Only run these commands if you intend to modify the files in `resources/css` or `resources/js`, or if you are developing the Vite configuration:

```powershell
Set-Location "C:\xampp\htdocs\attendance_app"
npm install
npm run build
```

The root Vite version requires Node `^20.19.0` or `>=22.12.0`. An older Node version can produce an engine warning or prevent the build.

The current Blade templates use committed vendor assets under `public/vendor`; they do not reference `@vite`.

## 15. Install the Fingerprint Server Runtime

Node.js is required for this separate application.

```powershell
Set-Location "C:\xampp\htdocs\attendance_app\fingerprint-server"
Copy-Item -LiteralPath ".env.example" -Destination ".env"
notepad .env
npm install
```

At minimum, confirm the database settings in `fingerprint-server\.env` point to the same database as Laravel. The database version must be the tested target from [DATABASE_COMPATIBILITY.md](DATABASE_COMPATIBILITY.md). If you are testing MariaDB 11.4 on port `3307`, update both `.env` files. Do not start the server with a real scanner yet unless the SDK is installed; otherwise it starts in simulation mode.

Full configuration and hardware steps are in [FINGERPRINT_SETUP.md](FINGERPRINT_SETUP.md).

Start it with the default script:

```powershell
npm start
```

`npm start` runs `watchdog.js`, which starts `server.js` and restarts it if it exits unexpectedly. The server listens on port `3001`.

## 16. Stage 1 Verification Checklist

Complete these checks before configuring hardware:

- [ ] The actual PHP and MariaDB versions are recorded in [DATABASE_COMPATIBILITY.md](DATABASE_COMPATIBILITY.md).
- [ ] The database version is either a tested candidate or is explicitly marked as unverified for software-only testing.
- [ ] `php artisan about` runs without errors.
- [ ] `php artisan migrate:status` shows all migrations as applied.
- [ ] `http://localhost:8000/up` returns a successful response.
- [ ] `/login` and `/register` load.
- [ ] A new account can be registered and reaches `/dashboard`.
- [ ] An employee can be created from `/users`.
- [ ] A schedule can be assigned to the employee.
- [ ] Manual attendance can be added from the attendance report.
- [ ] `/kiosk` loads.
- [ ] Visitor registration works with a photo upload or webcam capture.
- [ ] Visitor checkout works.
- [ ] Attendance reports and print pages load.
- [ ] Visitor log filters load.
- [ ] The fingerprint server reports `simulationMode: true` until a real scanner is available.

The visitor CSV export route exists, but the current source passes a datetime-cast value to a time-only Carbon format and raises `InvalidFormatException`. Do not count CSV export as verified until that source defect is fixed.

At this point, the software-only installation is complete for testing. This does not certify the database for production. Do not enter real data until the test gates in [DATABASE_COMPATIBILITY.md](DATABASE_COMPATIBILITY.md) pass, and do not treat simulation-mode fingerprint captures as attendance records.

## 17. Troubleshooting

### PowerShell Does Not Recognize PHP or Composer

Use the full path:

```powershell
& "C:\xampp\php\php.exe" -v
& "C:\ProgramData\ComposerSetup\bin\composer.bat" --version
```

The Composer path can differ depending on how it was installed. Run `where.exe composer` to locate it.

### Missing APP_KEY

Symptom: encryption or session errors, or a message that no application key is set.

Fix:

```powershell
Set-Location "C:\xampp\htdocs\attendance_app"
php artisan key:generate
php artisan optimize:clear
```

### Database Connection Refused or Access Denied

Check:

- MySQL is running in XAMPP.
- `DB_HOST` is `127.0.0.1` or `localhost`.
- `DB_PORT` is `3306` unless you changed it.
- `DB_DATABASE` is `attendance_app`.
- `DB_USERNAME` and `DB_PASSWORD` match the local MySQL account.
- The `pdo_mysql` extension is enabled.

Then clear cached configuration:

```powershell
php artisan config:clear
```

### Migrations Fail Because the Database Does Not Exist

Create it first:

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS attendance_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Troubleshooting the 1071 Key-Length Error

Symptoms can include:

```text
SQLSTATE[42000]: Syntax error or access violation: 1071 Specified key was too long; max key length is 767 bytes
```

This affects older MySQL/MariaDB configurations where the default InnoDB row format is not `DYNAMIC`, especially with a unique `VARCHAR(255)` email column under `utf8mb4`.

For a new, empty development database:

1. Check the setting:

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "SHOW VARIABLES LIKE 'innodb_default_row_format';"
```

2. Set `innodb_default_row_format=DYNAMIC` in the `[mysqld]` section of `C:\xampp\mysql\bin\my.ini`.
3. Restart MySQL.
4. Recreate the empty database if the failed migration left partial objects:

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "DROP DATABASE IF EXISTS attendance_app; CREATE DATABASE attendance_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

5. Run `php artisan migrate` again.

For an existing database, do not drop it. Create a backup first and apply a compatible row-format or index-length fix with the help of a database administrator.

Do not edit migrations merely to work around this unless you have reviewed the effect on existing installations.

### Routes Return 404 Under Apache

Check:

- Apache is running.
- `mod_rewrite` is enabled.
- `DocumentRoot` and `<Directory>` point to `...\attendance_app\public`.
- `AllowOverride All` is set for that directory.
- The `public\.htaccess` file exists.

### Storage Link or Photo Upload Fails

Check:

- `php artisan storage:link` has been run.
- `public\storage` exists.
- `storage\app\public` is writable by the web-server account.
- PHP's `fileinfo` extension is enabled.
- `upload_max_filesize` and `post_max_size` accommodate a 5 MB photo.

### Webcam Does Not Open

Camera access requires a secure browser context. Use:

- `http://localhost`
- `http://127.0.0.1`
- An HTTPS address

Many browsers block camera access on a plain HTTP LAN address such as `http://192.168.1.50`. Allow camera permission for the site.

### Port 8000 Is Already in Use

```powershell
Get-NetTCPConnection -LocalPort 8000 -State Listen
```

Stop the process using the port, or start Laravel on another port:

```powershell
php artisan serve --host=127.0.0.1 --port=8080
```

### Node Engine Warning During Root npm Install

The root `package.json` uses Vite 7.3.2. Install Node 22.12 or newer, or Node 20.19 or newer within the 20.x line. The fingerprint server itself does not require Vite, but using a compatible Node release avoids tooling problems.

## 18. Next Steps

1. Read [DATABASE_COMPATIBILITY.md](DATABASE_COMPATIBILITY.md) before selecting or changing the database.
2. Read [FINGERPRINT_SETUP.md](FINGERPRINT_SETUP.md) if biometric attendance is required.
3. Read [PROJECT_WORKFLOW.md](PROJECT_WORKFLOW.md) to understand attendance and visitor behavior.
4. Read [DEPLOYMENT.md](DEPLOYMENT.md) before moving the system to a permanent kiosk or server.
5. Keep [TECHNICIAN_QUICK_REFERENCE.md](TECHNICIAN_QUICK_REFERENCE.md) and [COMMAND_REFERENCE.md](COMMAND_REFERENCE.md) available for daily operations.
