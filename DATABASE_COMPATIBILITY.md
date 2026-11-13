# Database Compatibility Guide

This guide records what is known about the database versions used by the Employee Attendance System. It is written for a single Windows computer deployment.

> Status: no database migration, database install, service start, or database test was performed while preparing this document. The candidate versions below are not production-certified. Follow the test gates before storing real attendance or visitor data.

## 1. Status Labels Used in This Guide

| Label | Meaning |
| --- | --- |
| Verified by inspection | Confirmed by reading repository files or running read-only version commands |
| Verified by vendor source | Confirmed from an official vendor download or release page |
| Not tested | Not installed or exercised in this project yet |
| Hardware-dependent | Requires a physical ZK9500 scanner and a working SDK |
| Development baseline | Known to have been used during development, but not the recommended target for a new computer |

## 2. Known Application Requirements

The application is a Laravel 12 project requiring PHP `^8.2`. It uses:

- MySQL or MariaDB through Laravel's `mysql` connection.
- `utf8mb4` and `utf8mb4_unicode_ci`.
- InnoDB tables.
- JSON columns for `employee_schedules.schedule_days`.
- Enum columns for attendance status, employee status, and schedule days.
- Foreign keys with cascade deletes.
- A unique index on `users.email` with `VARCHAR(255)`.

The unique email index requires an InnoDB row format that supports the index length. A legacy 767-byte index limit can produce:

```text
SQLSTATE[42000]: Syntax error or access violation: 1071 Specified key was too long; max key length is 767 bytes
```

The application does not use JSON SQL query operators, full-text indexes, stored procedures, or triggers. It does use JSON columns, but stores and decodes JSON in PHP/JavaScript.

## 3. Verified Development Baseline

The existing local XAMPP installation was inspected read-only. No service was started and no configuration was changed.

| Item | Verified result |
| --- | --- |
| Client binary | `mysql.exe Ver 15.1 Distrib 10.4.32-MariaDB, for Win64 (AMD64)` |
| Server binary | `mysqld.exe Ver 10.4.32-MariaDB for Win64 on AMD64` |
| Windows file version | `10.4.32.0` |
| Local dump header | `Server version 10.4.32-MariaDB` |
| Dump file | `attendance_app_local_backup_20260914_162726.sql` |

The dump also contains older tables that are not part of the current migrations, including `students` and `student_attendances`. It is therefore not a clean schema baseline. Treat it as historical data that needs assessment before migration.

### 10.4.32 support status

MariaDB 10.4 reached community end of life on **June 18, 2024**. It remains useful as a development regression reference, but it should not be the default database for a new school computer.

## 4. Official Package Metadata

Metadata was checked on **November 13, 2026**. Official package listings describe what the vendor intends to ship; they do not replace an installed-version check.

### XAMPP for Windows

| XAMPP version | PHP version | MariaDB listed by Apache Friends |
| --- | --- | --- |
| 8.5.12 | 8.5.12 | 10.11.16 |
| 8.4.27 | 8.4.27 | 10.11.16 |
| 8.3.36 | 8.3.36 | 10.11.16 |
| 8.2.12 | 8.2.12 | 10.4.32 |

Official source: <https://www.apachefriends.org/download.html>

Important:

- The C1 candidate is **the actual MariaDB version installed on the test computer**, not automatically 10.11.16.
- Record the result of `SELECT VERSION()` and `mariadbd --version` before testing.
- If the actual version is not MariaDB 10.11.x, stop and reassess the candidate.
- XAMPP 8.3.36 currently lists MariaDB 10.11.16. MariaDB 10.11.19 is the current 10.11 release, so the XAMPP bundle may lag the latest patch.

### MariaDB 11.4 standalone

The current MariaDB 11.4 LTS patch at the time of this check is **MariaDB 11.4.13**, released **August 22, 2026**.

| Item | Value |
| --- | --- |
| Official download page | <https://mariadb.org/download/> |
| Official release metadata | <https://downloads.mariadb.org/rest-api/mariadb/11.4.13/> |
| Windows x64 MSI | <https://downloads.mariadb.org/rest-api/mariadb/11.4.13/mariadb-11.4.13-winx64.msi> |
| MSI SHA-256 | `5af228931e6e13c599060d9eaf78f71a9fa62879a3d3fa9327cb458b756a6c04` |

Verify the SHA-256 checksum before installation. If a newer 11.4.x patch is published before the test, pause and record the replacement version and checksum. Do not silently substitute a different version.

## 5. Candidate Definitions

| Candidate | Database | Purpose | Status |
| --- | --- | --- | --- |
| C0 | MariaDB 10.4.32 | Known development baseline and dump source | Verified locally; not for new production |
| C1 | Actual MariaDB version installed by XAMPP 8.3.36, expected 10.11.x | First deployment candidate | Not tested |
| C2 | MariaDB 11.4.13 standalone | Longer-support deployment candidate | Not tested |

C1 and C2 must use the same application code, PHP test environment, and test data so that the database comparison is controlled.

## 6. Recording the Actual C1 or C2 Version

Run these commands on the isolated test environment after installation:

```powershell
& "C:\xampp\mysql\bin\mariadbd.exe" --version
```

If `mariadbd.exe` is not present, use:

```powershell
& "C:\xampp\mysql\bin\mysqld.exe" --version
```

Then, only while the database service is running:

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "SELECT VERSION() AS version, @@version_comment AS comment;"
```

Record the outputs in the evidence table at the end of this document. Use the measured version in all test labels and technician documentation.

Also record:

```sql
SHOW VARIABLES LIKE 'innodb_default_row_format';
SHOW VARIABLES LIKE 'sql_mode';
SHOW VARIABLES LIKE 'character_set_server';
SHOW VARIABLES LIKE 'collation_server';
```

`innodb_default_row_format` should be `DYNAMIC` before testing the current migrations.

## 7. Verifying the 10.4.32 Baseline Without Changing the Existing Environment

The existing development installation must remain untouched. Use read-only evidence first.

### Read-only checks that do not start the database

```powershell
& "C:\xampp\mysql\bin\mysql.exe" --version
& "C:\xampp\mysql\bin\mysqld.exe" --version
```

```powershell
Get-Item -LiteralPath "C:\xampp\mysql\bin\mysqld.exe" |
    Select-Object FullName, Length, LastWriteTime,
        @{Name = "FileVersion"; Expression = { $_.VersionInfo.FileVersion } },
        @{Name = "ProductVersion"; Expression = { $_.VersionInfo.ProductVersion } }
```

```powershell
sc.exe qc mysql
```

Reading the service configuration does not start the service. The service name may differ on another computer; use `Get-Service *mysql*` and `Get-Service *maria*` to locate it.

Read the existing database error log from the XAMPP MySQL data directory. Do not edit it.

Read the first part of the local dump:

```powershell
Get-Content -LiteralPath "attendance_app_local_backup_20260914_162726.sql" -TotalCount 20
```

The dump header identifies the source server. The binary command and file-version output identify the installed server. Record both; they are different evidence.

Do not run `SELECT VERSION()` on the existing host unless the service is already running and you have permission to query it. Do not start the service just to obtain the version.

### Optional C0 baseline VM

If application-level baseline behavior must be compared, build a disposable VM with XAMPP 8.2.12 and restore a copy of the dump there. Do not install, migrate, or modify the existing host environment.

## 8. Test Matrix

Each candidate must pass all applicable tests before it can be recommended.

| Test | C0 baseline | C1: XAMPP 8.3.36 | C2: MariaDB 11.4.13 |
| --- | --- | --- | --- |
| Exact installed version recorded | Verified | Not tested | Not tested |
| `innodb_default_row_format` is `DYNAMIC` | Read-only evidence pending | Not tested | Not tested |
| Fresh database and all migrations | Not rerun | Not tested | Not tested |
| Unique `users.email` index | Not rerun | Not tested | Not tested |
| JSON columns and enum columns | Not rerun | Not tested | Not tested |
| Foreign keys and cascade deletes | Not rerun | Not tested | Not tested |
| Login, registration, dashboard | Not rerun | Not tested | Not tested |
| Employees and schedules | Not rerun | Not tested | Not tested |
| Manual attendance and reports | Not rerun | Not tested | Not tested |
| Visitor registration, checkout, and log | Not rerun | Not tested | Not tested |
| Fingerprint server database access | Not rerun | Not tested | Not tested |
| Local 10.4.32 dump import | Source only | Not tested | Not tested |
| Backup and verified restore | Not rerun | Not tested | Not tested |
| PHP 8.3 compatibility | Not tested | Not tested | Not tested |

## 9. C1 Test Procedure

1. Use an isolated Windows VM or spare computer.
2. Install XAMPP 8.3.36 from the official Apache Friends download page.
3. Record the installer filename and official checksum.
4. Start Apache and the bundled MariaDB service.
5. Record the actual MariaDB version and server variables listed in section 6.
6. If the actual version is not MariaDB 10.11.x, stop and reassess.
7. Create a clean test database with `utf8mb4` and `utf8mb4_unicode_ci`.
8. Configure a test-only Laravel `.env`.
9. Run the migrations from a clean database.
10. Run the application smoke tests.
11. Configure the fingerprint server against the same test database and test its database-backed endpoints.
12. Import a copy of the 10.4.32 dump into a separate scratch database and assess the migration path.
13. Test a database backup and a restore into a second scratch database.
14. Record results in the evidence table. Do not use real school data.

If XAMPP installs MariaDB 10.11.16, record the version exactly. MariaDB 10.11.19 is the current 10.11 release; evaluate the patch-level difference separately rather than assuming the bundle is fully current.

## 10. C2 Test Procedure

1. Use a separate clean snapshot or VM.
2. Install XAMPP 8.3.36 for Apache and PHP only.
3. Verify the XAMPP MariaDB service is stopped.
4. Verify the MariaDB 11.4.13 MSI SHA-256 checksum from section 4.
5. Install MariaDB 11.4.13 as a separate service.
6. During side-by-side testing, use port `3307` for MariaDB 11.4 and keep the XAMPP database stopped.
7. Record the actual `SELECT VERSION()` output.
8. Configure both the Laravel `.env` and `fingerprint-server\.env` to use the same test database and port.
9. Repeat the C1 tests from section 9.
10. Test service startup, authentication, client tools, and port behavior.

Do not call C2 production-ready until the full matrix passes.

## 11. Existing Data and the 10.4.32 Dump

The local dump contains both current-style tables and older student tables. Before importing it into either candidate:

1. Preserve the original file and record its SHA-256 hash.
2. Restore a copy into a scratch database created by the 10.4.32 baseline, not the production database.
3. Inventory tables, columns, indexes, foreign keys, and row counts.
4. Compare the dump schema with `database/migrations`.
5. Decide whether to:
   - create the current schema and import selected data;
   - restore the full dump and then run migrations; or
   - write a custom data migration.
6. Test the chosen path on C1 and C2.
7. Verify users, employees, schedules, attendance, visitors, and fingerprint templates after migration.

If the dump requires a custom data-migration script or source change, stop and request separate approval. Source-code changes are outside this documentation scope.

## 12. Decision Rules

- MariaDB 10.4.32 is a development baseline only. It is end-of-life and is not recommended for a new school computer.
- C1 and C2 are candidates, not certified production targets.
- Prefer C1 if it passes fully and the support window is acceptable.
- Prefer C2 only if it passes fully and the longer support window justifies the separate database installation.
- A candidate fails if migrations, the unique email index, JSON columns, foreign keys, application workflows, fingerprint-server access, dump migration, or backup/restore fail.
- No final database recommendation can be made until the evidence table is completed.

Community support windows:

- MariaDB 10.11: community maintenance until February 2028.
- MariaDB 11.4: community maintenance until May 2029.
- MariaDB 10.4: community end of life June 18, 2024.

## 13. Professor Laptop Diagnostic Checklist

The exact professor-laptop failure cannot be diagnosed without its database version and error. Collect:

```sql
SELECT VERSION();
SELECT @@version_comment;
SELECT @@innodb_default_row_format;
SELECT @@sql_mode;
SHOW VARIABLES LIKE 'character_set_server';
SHOW VARIABLES LIKE 'collation_server';
```

Also record:

- The exact Laravel or SQL error message.
- The `storage\logs\laravel.log` error entry.
- The database client and server versions.
- The PHP version.
- Whether the failure occurs during migration, login, import, or normal use.
- The command or tool used to restore the database.
- The dump header and source server version.

Likely causes to check, not conclusions:

- Database server older than the application/framework minimum.
- Legacy 767-byte index limit or non-`DYNAMIC` row format.
- A MariaDB dump restored into an older or different server.
- A dump with older student-era tables and schema drift.
- An import that failed partway through.
- A database connection or credential error unrelated to version.

## 14. Backup and Rollback Rules

- Back up the database before any install, migration, import, or port change.
- Test the restore before trusting the backup.
- Prefer a side-by-side candidate and keep the old database until acceptance.
- Keep the 10.4.32 baseline and its dump available for rollback.
- Do not uninstall or replace an existing database without owner approval.
- Never use `php artisan migrate:fresh` or `DROP DATABASE` on real attendance or visitor data.
- On failure, switch `.env` and `fingerprint-server\.env` back to the previous database and port.

## 15. Evidence Table

Complete this table during the actual test. Do not mark a row complete without command output or a test record.

| Field | C0 | C1 | C2 |
| --- | --- | --- | --- |
| Installer source | Existing XAMPP | | |
| Installer checksum | | | |
| Installed server version | 10.4.32-MariaDB | | |
| `innodb_default_row_format` | | | |
| Migration result | Not rerun | | |
| Application smoke test | Not rerun | | |
| Fingerprint-server DB test | Not rerun | | |
| Dump import result | Not tested | | |
| Backup/restore result | Not rerun | | |
| Tester and date | | | |
| Decision | Development baseline only | | |

## 16. Related Guides

- One-computer installation: [TECHNICIAN_SETUP_GUIDE.md](TECHNICIAN_SETUP_GUIDE.md)
- Quick commands: [TECHNICIAN_QUICK_REFERENCE.md](TECHNICIAN_QUICK_REFERENCE.md)
- Detailed installation: [INSTALLATION.md](INSTALLATION.md)
- Deployment: [DEPLOYMENT.md](DEPLOYMENT.md)
- Commands: [COMMAND_REFERENCE.md](COMMAND_REFERENCE.md)
