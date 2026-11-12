# Attendance Monitoring System - Deployment & Setup Guide

This guide walks through deploying the Attendance Monitoring System to another Windows device, from a blank machine to a running system.

The project has three parts that must all be running:

1. **Laravel web application** (PHP) - dashboard, schedules, reports, visitor registration.
2. **MySQL/MariaDB database** - shared data store for the Laravel app and fingerprint server.
3. **Fingerprint server** (Node.js) - talks to the ZKTeco ZK9500 scanner and records attendance.

---

## 1. Deployment Topology

Choose one of these setups before installing.

### Option A - Single all-in-one PC (recommended for a kiosk)

Everything runs on the same PC: XAMPP, Laravel, MySQL, fingerprint server, scanner, webcam, browser.

```
[Browser] -> http://localhost:8000  -> Laravel (PHP)
                     |
                     +-> http://127.0.0.1:3001 -> Fingerprint server -> ZK9500
                     |
                     +-> MySQL (127.0.0.1:3306)
```

### Option B - Central server + kiosk PC(s)

Laravel + MySQL run on a server. Each kiosk PC runs its own copy of the fingerprint server and browser, and connects to the central database.

```
[Kiosk browser] -> http://<server-ip>:8000 -> Laravel on server
       |
       +-> http://127.0.0.1:3001 -> Fingerprint server on kiosk -> ZK9500 on kiosk
       |
       +-> MySQL on server (reachable from kiosk)
```

> **Important:** The scanner pages in this project call `http://127.0.0.1:3001` and `http://localhost:3001` directly. Therefore the **fingerprint server must run on the same machine as the browser and the scanner**, even when Laravel runs on a separate server. For Option B, copy the `fingerprint-server` folder to each kiosk PC and point its `.env` at the central database.

---

## 2. Hardware Requirements

| Item | Requirement | Notes |
|------|-------------|-------|
| Computer | Windows 10/11, 64-bit | The ZKTeco SDK DLL (`libzkfp.dll`) bundled in this project is Windows 64-bit. |
| CPU / RAM | Dual-core, 4 GB RAM minimum; 8 GB recommended | Server + kiosk combined benefit from 8 GB. |
| Disk | 2 GB free minimum | Includes dependencies and uploaded visitor photos. |
| USB ports | 1+ available | For the fingerprint scanner. |
| Fingerprint scanner | ZKTeco **ZK9500** (optional) | If absent, the fingerprint server runs in simulation mode. |
| Windows Hello reader | Built-in or USB (optional) | Alternative biometric method via WebAuthn. |
| Webcam | USB or built-in (optional) | Required for "Capture with Webcam" on the visitor registration page. |
| Printer | Any Windows-supported printer (optional) | Used by the print/export pages. |
| Network | LAN connection if using Option B | Needed for kiosk-to-server access. |

### Scanner notes

- The project includes `fingerprint-server/sdk/libzkfp.dll`. For reliable operation, also install the official **ZKTeco ZKFinger SDK** and the **Microsoft Visual C++ 2015-2022 Redistributable (x64)**.
- Default Windows driver may be enough for the scanner to appear in Device Manager under **Biometric devices**.
- If no scanner is detected, the server logs `Running in simulation mode` and still starts.

---

## 3. Software Requirements

| Software | Minimum version | Notes |
|----------|-----------------|-------|
| Windows | 10 or 11 (64-bit) | Linux/macOS is not supported for the ZKTeco SDK integration. |
| XAMPP | Any recent build | Must provide Apache, PHP 8.2+, and MariaDB 10.4+/MySQL 8. |
| PHP | **8.2+** | Tested with 8.2.12. Laravel 12 requires PHP 8.2 or newer. |
| Composer | 2.x | Tested with 2.8.1. |
| Node.js | **20.19+ or 22.12+** | Required by Vite 7. Tested with Node 22.9.0. |
| npm | Bundled with Node.js | Tested with npm 10.8.3. |
| MariaDB/MySQL | MariaDB 10.4+ or MySQL 8+ | The existing dump was created with MariaDB 10.4.32. |
| Browser | Latest Chrome or Edge | Required for WebAuthn and webcam access. |
| Git | Optional | Only needed if cloning the repository. |
| ZKTeco ZKFinger SDK | Latest available | Optional but recommended for real scanner use. |
| VC++ Redistributable | 2015-2022 x64 | Required by the scanner SDK. |

### Required PHP extensions

Enable these in `C:\xampp\php\php.ini` (remove the leading `;` if disabled):

```
pdo_mysql
mbstring
openssl
fileinfo
ctype
json
tokenizer
xml
gd
bcmath
curl
```

Verify with:

```powershell
php -m
```

Also check upload limits for visitor photos (the app allows 5 MB per photo):

```powershell
php -i | Select-String "upload_max_filesize|post_max_size"
```

If either is below `5M`, raise them in `php.ini`:

```ini
upload_max_filesize = 20M
post_max_size = 20M
```

Restart Apache after editing `php.ini`.

---

## 4. Copy the Project

Place the project at:

```
C:\xampp\htdocs\attendance_app
```

Avoid paths with spaces or special characters.

If copying from Git:

```powershell
git clone <repository-url> C:\xampp\htdocs\attendance_app
```

If copying from another machine, you may exclude `node_modules` and `vendor`; they will be reinstalled in the next steps. Do **not** copy `.env` from the old device unless you intentionally want the same secrets and configuration; create a new one below.

---

## 5. Step-by-Step Installation

Run PowerShell commands from the project root:

```powershell
Set-Location C:\xampp\htdocs\attendance_app
```

### Step 1 - Start XAMPP services

Open **XAMPP Control Panel** and start:

- **Apache**
- **MySQL**

Confirm MySQL is listening on port `3306`.

### Step 2 - Create the database

Using phpMyAdmin (http://localhost/phpmyadmin) or the MySQL CLI:

```sql
CREATE DATABASE attendance_app
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
```

CLI version:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE attendance_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Step 3 - Create the Laravel `.env` file

```powershell
Copy-Item .env.example .env
```

Edit `.env` and set at least these values:

```env
APP_NAME="Attendance Monitoring System"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=attendance_app
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

Notes:

- Use `APP_ENV=local` and `APP_DEBUG=true` only while troubleshooting.
- Set a real `DB_PASSWORD` if MySQL is not using the default XAMPP root account.
- Keep `APP_URL` consistent with how users will reach the app.

### Step 4 - Generate the application key

```powershell
php artisan key:generate
```

This writes `APP_KEY` into `.env`. Back it up with the rest of `.env`; encrypted data cannot be recovered without it.

### Step 5 - Install PHP dependencies

```powershell
composer install
```

For a production deployment you can use:

```powershell
composer install --no-dev --optimize-autoloader
```

### Step 6 - Install frontend dependencies and build assets

```powershell
npm install
npm run build
```

This produces the compiled CSS/JS in `public/build`. If you see **"Vite manifest not found"**, run `npm run build` again.

### Step 7 - Create the database schema

**Fresh installation:**

```powershell
php artisan migrate --force
```

**Migration from an existing device (keep current data):**

1. Copy the dump to the new device, for example `attendance_app_local_backup_<date>.sql`.
2. Import it into the empty `attendance_app` database:

```powershell
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root attendance_app < attendance_app_local_backup_20260914_162726.sql"
```

3. Apply any newer migrations that are not in the dump:

```powershell
php artisan migrate --force
```

> The SQL dump files in the project root are ignored by Git and may not exist on the new device. Copy the backup separately and treat it as sensitive data (it contains employee and attendance records).

### Step 8 - Link public storage

Visitor photos are stored under `storage/app/public` and served from `public/storage`.

```powershell
php artisan storage:link
```

If the link already exists and points to an old path, delete `public\storage` first, then run the command again.

### Step 9 - Clear and rebuild caches

```powershell
php artisan optimize:clear
```

For production, after configuration is final:

```powershell
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 10 - Create the first admin account

Either:

- Open `http://localhost:8000/register` and create an account, or
- Seed a test account:

```powershell
php artisan db:seed
```

The seeder creates `test@example.com` with password `password`. **Change or delete this account immediately in production.**

---

## 6. Fingerprint Server Setup

The fingerprint server is a separate Node.js app in `fingerprint-server`.

### Step 1 - Create its `.env`

```powershell
Set-Location C:\xampp\htdocs\attendance_app\fingerprint-server
Copy-Item .env.example .env
```

Edit `fingerprint-server\.env`:

```env
# Database - must point to the same database as Laravel
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASSWORD=
DB_NAME=attendance_app
DB_SSL=false

# Server
PORT=3001
WS_PORT=3002

# Use plain HTTP for local/kiosk deployments (recommended default)
FINGERPRINT_SSL=false

# Browser origins allowed to call this server.
# Include the URL people use to open the Laravel app.
CORS_ORIGINS=http://localhost:8000,http://127.0.0.1:8000

# ZKTeco SDK location (only needed for a real scanner)
ZKTECO_SDK_PATH=C:/Program Files/ZKTeco/ZK9500/

# Laravel API URL
LARAVEL_API_URL=http://localhost:8000/api

# Matching threshold (0-100, higher = stricter)
FINGERPRINT_THRESHOLD=50
MAX_TEMPLATE_SIZE=2048
```

For Option B (central database), set `DB_HOST` to the server IP and `DB_SSL` appropriately. Also add every kiosk origin to `CORS_ORIGINS`.

### Step 2 - Install dependencies

```powershell
npm install
```

### Step 3 - Start the server

```powershell
npm start
```

`npm start` runs `watchdog.js`, which automatically restarts `server.js` if it crashes. For development with auto-reload:

```powershell
npm run dev
```

Expected log output:

```
Fingerprint Server running on port 3001 (HTTP)
REST API: http://localhost:3001/api
WebSocket: ws://localhost:3001/ws
Health Check: http://localhost:3001/health
```

### Step 4 - Verify the scanner

```powershell
Invoke-RestMethod http://127.0.0.1:3001/health
Invoke-RestMethod http://127.0.0.1:3001/api/status
```

A working scanner reports a device count greater than zero. If no scanner is found, the server starts in **simulation mode**; enrollment and verification then use generated templates and are for testing only.

### Scanner / DLL notes

The service looks for `libzkfp.dll` in this order:

```
C:\Program Files\ZKTeco\SDK\libzkfp.dll
C:\Program Files (x86)\ZKTeco\SDK\libzkfp.dll
C:\Windows\System32\libzkfp.dll
C:\Windows\SysWOW64\libzkfp.dll
<project>\fingerprint-server\sdk\libzkfp.dll
<project>\sdk\libzkfp.dll
```

The bundled copy is at `fingerprint-server\sdk\libzkfp.dll`. To use the official SDK instead:

1. Download **ZKFinger SDK** from https://www.zkteco.com/en/Downloads.
2. Install it and note the install folder.
3. Copy `libzkfp.dll` to `fingerprint-server\sdk\` or update `ZKTECO_SDK_PATH`.
4. Install the **Visual C++ 2015-2022 Redistributable (x64)**.
5. Restart the fingerprint server.

Use 64-bit Node.js. A 32-bit Node process cannot load the 64-bit DLL.

### Optional - Auto-start on boot

To keep the fingerprint server running after reboot, create a Windows Task Scheduler task:

- **Program:** `C:\Program Files\nodejs\node.exe`
- **Arguments:** `watchdog.js`
- **Start in:** `C:\xampp\htdocs\attendance_app\fingerprint-server`
- **Trigger:** At startup
- **Run whether user is logged on or not** (as appropriate)

---

## 7. Start the Laravel Application

### Development / kiosk mode

```powershell
Set-Location C:\xampp\htdocs\attendance_app
php artisan serve --host=0.0.0.0 --port=8000
```

Open:

| Page | URL |
|------|-----|
| Login | http://localhost:8000/login |
| Dashboard | http://localhost:8000/dashboard |
| Visitor registration | http://localhost:8000/visitor/register |
| Visitor log | http://localhost:8000/visitor |
| Kiosk | http://localhost:8000/kiosk |
| ZK9500 scan | http://localhost:8000/scan/employee/zk9500 |
| ZK9500 enrollment | http://localhost:8000/scan/employee/zk9500/enroll |
| WebAuthn scan | http://localhost:8000/scan/employee |

### Apache virtual host (optional)

Configure Apache to serve the `public` directory as the document root. Example `httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName attendance.local
    DocumentRoot "C:/xampp/htdocs/attendance_app/public"

    <Directory "C:/xampp/htdocs/attendance_app/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Then add `127.0.0.1 attendance.local` to `C:\Windows\System32\drivers\etc\hosts`, restart Apache, and set `APP_URL=http://attendance.local`.

Do **not** point the document root at the project root; the `public` folder must be the web root.

### Secure-context requirement

WebAuthn (Windows Hello) and webcam access require a **secure context**:

- `http://localhost` and `http://127.0.0.1` are treated as secure.
- Any other host over plain HTTP (for example `http://192.168.1.10:8000`) will **not** allow WebAuthn or camera access.

For LAN access with biometrics or webcam capture, serve the app over HTTPS (reverse proxy such as Caddy/IIS/nginx, or XAMPP with TLS). If you run the app over HTTPS and still want the local fingerprint server, either:

- Keep the fingerprint server on HTTP on the same machine (browsers may block mixed content), or
- Enable its HTTPS mode by setting `FINGERPRINT_SSL=true` and providing certificates in `fingerprint-server/certs`, then update the scanner pages accordingly.

Always open the app with the same hostname used in `APP_URL` and `CORS_ORIGINS`.

---

## 8. Ports and Firewall

| Port | Service | Exposure |
|------|---------|----------|
| 8000 | Laravel (`php artisan serve`) | LAN if using Option B, otherwise localhost |
| 80 / 443 | Apache (if using a virtual host) | LAN if using Option B |
| 3001 | Fingerprint server REST/WebSocket | **Localhost only** on each kiosk |
| 3306 | MySQL/MariaDB | Localhost, or LAN restricted to kiosk IPs |

Recommendations:

- Keep `3001` bound to localhost. The frontend expects `127.0.0.1:3001`.
- Never expose MySQL directly to the internet.
- On the server, allow only the kiosk IPs through Windows Firewall for ports `8000` (or `80`/`443`) and `3306`.

---

## 9. Post-Installation Verification Checklist

- [ ] `php artisan about` runs without errors.
- [ ] `php artisan migrate:status` shows all migrations as `Ran`.
- [ ] Login page loads and the first admin can sign in.
- [ ] `http://127.0.0.1:3001/health` returns `"status": "ok"`.
- [ ] `http://127.0.0.1:3001/api/status` reports the scanner (or simulation mode).
- [ ] Visitor registration page opens, webcam preview works, and a photo is saved.
- [ ] Uploaded visitor photos display (confirms `storage:link`).
- [ ] Employee pages list existing employees.
- [ ] Schedules can be created and edited.
- [ ] ZK9500 enrollment succeeds and the fingerprint is stored.
- [ ] ZK9500 scan records time-in/time-out for the correct employee.
- [ ] Windows Hello enrollment and scan work (if used).
- [ ] Reports, print pages, CSV export, and PPTX export open correctly.
- [ ] Existing data is present (when migrating from a backup).

---

## 10. Hardware Configuration

### ZKTeco ZK9500 fingerprint scanner

1. Connect the scanner to a USB port (prefer USB 2.0 directly, not a hub).
2. Install the ZKTeco driver/SDK if Windows does not detect it.
3. Open **Device Manager** and confirm the device appears under **Biometric devices**.
4. Install the Visual C++ redistributable.
5. Place `libzkfp.dll` where the server can find it (see Section 6).
6. Restart the fingerprint server and check `/api/status`.
7. If matches are too strict or too loose, adjust `FINGERPRINT_THRESHOLD` (default `50`; the service also has a fallback default of `75`).

### Windows Hello (alternative to ZK9500)

1. Open **Settings > Accounts > Sign-in options > Fingerprint recognition (Windows Hello)** and enroll the Windows user.
2. In the app, open the WebAuthn enrollment page for an employee (`/scan/employee`).
3. Enroll and test with the scan page.

Windows Hello authenticates the Windows user, not a specific employee, so use it only where that mapping is acceptable.

### Webcam (visitor photos)

1. Connect the webcam and confirm it works in the Windows Camera app.
2. Open `/visitor/register`, click **Capture with Webcam**, and allow camera permission in the browser.
3. Use `http://localhost` or HTTPS, otherwise the browser blocks camera access.

### Printer (optional)

Open the print pages from the app (`employee.print`, `employees.print`) and print through the browser. Ensure the printer is installed in Windows.

---

## 11. Troubleshooting

| Problem | Likely cause / fix |
|---------|--------------------|
| `could not find driver` or `SQLSTATE[HY000]` | `pdo_mysql` is disabled. Enable it in `php.ini` and restart Apache. |
| `No application encryption key has been specified` | Run `php artisan key:generate`. |
| `Access denied for user 'root'` | Set the correct `DB_USERNAME` / `DB_PASSWORD` in `.env`. |
| `Unknown database 'attendance_app'` | Create the database (Section 5, Step 2). |
| `SQLSTATE[HY000] [2002] Connection refused` | MySQL is not running or is on a different port. |
| `Vite manifest not found` | Run `npm install` and `npm run build`. |
| Visitor photos return 404 | Run `php artisan storage:link` and check `public/storage`. |
| White page / 500 error | Set `APP_DEBUG=true` temporarily, check `storage/logs/laravel.log`, then set it back to `false`. |
| Scanner not detected | Check USB, driver, Device Manager, DLL location, and 64-bit Node. See Section 6. |
| `Running in simulation mode` | No scanner or DLL found. Enrollment and verification are simulated. |
| `Failed to load libzkfp.dll` | Install VC++ x64 redistributable, install the ZKTeco SDK, and verify the DLL path/architecture. |
| CORS error in browser console | Add the app origin to `CORS_ORIGINS` in `fingerprint-server/.env` and restart the server. |
| `Permission denied ... loopback address space` | The browser is blocking public-to-loopback access. Serve the app from `localhost`, or use the HTTPS fingerprint-server mode with certificates. |
| WebAuthn not available | Open the app at `http://localhost:8000` (not `127.0.0.1` and not a LAN IP over HTTP), or use HTTPS. |
| Camera not available | Grant camera permission, close other apps using the webcam, and use `localhost`/HTTPS. |
| Port 3001 already in use | Stop the conflicting process (`Get-NetTCPConnection -LocalPort 3001`) or change `PORT` in both server `.env` and the scanner page URLs. |
| Port 8000 already in use | Use `php artisan serve --port=8001` and update `APP_URL`/`CORS_ORIGINS`. |
| Changes to `.env` ignored | Run `php artisan optimize:clear` (or `config:clear`). |
| Reverted to old config after deploy | You cached config. Run `php artisan optimize:clear`, then re-cache if desired. |

Log locations:

- Laravel: `storage\logs\laravel.log`
- Fingerprint server: the console window running `npm start`

---

## 12. Updating an Existing Deployment

```powershell
Set-Location C:\xampp\htdocs\attendance_app
git pull                      # if deployed via Git
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Then restart the fingerprint server:

```powershell
Set-Location fingerprint-server
npm install
npm start
```

After pulling updates, always run `php artisan migrate --force`; migrations are additive and safe to run repeatedly.

---

## 13. Backup and Restore

### What to back up

- MySQL database `attendance_app`
- `storage\app\public` (visitor photos)
- `.env` (contains `APP_KEY` and credentials)
- `fingerprint-server\.env`
- Optional: `fingerprint-server\certs` (TLS certificates)

### Database backup

```powershell
C:\xampp\mysql\bin\mysqldump.exe -u root attendance_app > attendance_app_backup.sql
```

### Database restore

```powershell
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root attendance_app < attendance_app_backup.sql"
```

Never commit `.env`, SQL dumps, or certificate private keys to version control. The repository's `.gitignore` already excludes them.

---

## 14. Production Security Checklist

- [ ] `APP_ENV=production` and `APP_DEBUG=false`.
- [ ] Strong, unique `APP_KEY` (generated on the new device).
- [ ] Non-empty MySQL root password, or a dedicated database user with limited privileges.
- [ ] Default seeded accounts removed or passwords changed.
- [ ] App served over HTTPS, especially on a LAN.
- [ ] Fingerprint server (port `3001`) restricted to localhost.
- [ ] MySQL (port `3306`) not reachable from untrusted networks.
- [ ] Windows Firewall allows only required ports and kiosk IPs.
- [ ] Automatic database and storage backups scheduled.
- [ ] `.env` files protected from other Windows users.
- [ ] Run `php artisan optimize` after configuration changes.

---

## 15. Quick Command Cheat Sheet

```powershell
# Project root
Set-Location C:\xampp\htdocs\attendance_app

# One-time setup
Copy-Item .env.example .env
php artisan key:generate
composer install
npm install
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear

# Start the web app (kiosk/local)
php artisan serve --host=0.0.0.0 --port=8000

# Fingerprint server (separate terminal)
Set-Location C:\xampp\htdocs\attendance_app\fingerprint-server
Copy-Item .env.example .env
npm install
npm start

# Health checks
Invoke-RestMethod http://127.0.0.1:3001/health
Invoke-RestMethod http://127.0.0.1:3001/api/status
```
