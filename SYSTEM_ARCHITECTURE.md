# System Architecture Diagram

## Overview

A system architecture diagram is a visual blueprint of a system, mapping its core components, their relationships, and how data flows between them, acting as a shared, high-level guide for developers, architects, and stakeholders to understand structure, communication, and potential issues for planning and development. It's like a building's blueprint.

**The Attendance & Visitor Registration System** uses a browser-based Blade frontend that communicates via Routes with Laravel Controllers responsible for attendance tracking, visitor registration, schedule management, and user administration, which in turn reads and writes data to a relational database (SQLite/MySQL) and stores visitor photos in system storage.

---

## 📊 Diagram File

A professional diagram file has been created for you:

**File:** `docs/system_architecture_diagram.drawio`

### How to Open and Edit

1. **Option 1: Draw.io Online (Recommended)**
   - Go to [https://app.diagrams.net/](https://app.diagrams.net/)
   - Click "Open Existing Diagram"
   - Select the file `system_architecture_diagram.drawio`
   - Edit and export as PNG, PDF, or other formats

2. **Option 2: VS Code Extension**
   - Install "Draw.io Integration" extension in VS Code
   - Open the `.drawio` file directly in VS Code

3. **Option 3: Desktop App**
   - Download Draw.io desktop from [https://www.drawio.com/](https://www.drawio.com/)
   - Open the file

### How to Export as Image

1. Open the diagram in Draw.io
2. Go to **File → Export as → PNG** (or PDF, SVG, etc.)
3. Save the image file
4. Insert into your documentation or presentation

---

## 🏗️ Architecture Layers

### Layer 1: Client Layer
| Component | Description |
|-----------|-------------|
| **Device** | Computer/laptop/tablet used to access the system |
| **System Users** | Administrators and staff who use the system |
| **RFID Reader/Webcam** | Hardware devices for attendance scanning and photo capture |

### Layer 2: Web Browser
| Component | Description |
|-----------|-------------|
| **Web Server** | Apache server (via XAMPP) that hosts the Laravel application |

### Layer 3: Application Layer (View)
| Component | Description |
|-----------|-------------|
| **System Views (Blade UI/UX)** | Laravel Blade templates that render the user interface |
| - dashboard.blade.php | Main dashboard view |
| - attendance/*.blade.php | Attendance log views (student, employee, visitor) |
| - visitor/register.blade.php | Visitor registration form with photo capture |
| - scan/*.blade.php | RFID scanning interfaces |
| - users/index.blade.php | User management interface |

### Layer 4: Application Layer (Route and Logic)
| Component | Description |
|-----------|-------------|
| **System Controllers** | Laravel controllers that handle business logic |
| - DashboardController | System overview and statistics |
| - AttendanceController | Attendance log viewing and export |
| - VisitorController | Visitor registration, checkout, photo upload |
| - ScanController | RFID/barcode scanning operations |
| - ScheduleController | Schedule CRUD operations |
| - UserController | User/Employee/Student management |

### Layer 5: Data Layer
| Component | Description |
|-----------|-------------|
| **App Private File Storage** | Stores visitor photos (`storage/app/public/visitors/`) |
| **Database Server** | SQLite/MySQL database engine |
| **Database** | Stores all system data (users, employees, students, visitors, schedules, attendances) |

---

## 🔄 Data Flow

```
User → Device → Web Browser → Web Server (Apache)
                                    ↓
                            Blade Views (UI/UX)
                                    ↓
                         Laravel Controllers (Logic)
                              ↙         ↘
                    File Storage      Database
                  (Visitor Photos)  (System Data)
```

### Flow Description

1. **Users access system via web** - Users open a browser and navigate to the system URL
2. **Web Server displays corresponding views** - Apache serves the Laravel Blade templates
3. **Actions in views trigger controller logic** - Form submissions and button clicks call controller methods
4. **CRUD Actions** - Controllers read/write data to the database via Eloquent ORM
5. **Stores files** - Visitor photos are saved to the file storage system

---

## 📋 Database Tables

| Table | Purpose |
|-------|---------|
| `users` | System user accounts |
| `employees` | Employee records |
| `students` | Student records |
| `visitors` | Visitor registration logs |
| `schedules` | Work/class schedules |
| `student_attendances` | Student attendance records |
| `cache` | Application cache |
| `jobs` | Background job queue |

---

## 🛠️ Technology Stack

| Layer | Technology |
|-------|------------|
| **Frontend** | HTML5, CSS3, JavaScript, Bootstrap 5 |
| **Template Engine** | Laravel Blade |
| **Backend Framework** | Laravel 11+ |
| **Language** | PHP 8.2+ |
| **Database** | SQLite / MySQL |
| **Web Server** | Apache (XAMPP) |
| **File Storage** | Laravel Storage Facade |
| **Hardware Support** | RFID Reader, Webcam, Biometric Scanner |

---

## 📝 For Your Documentation

Copy this paragraph for your thesis/project documentation:

> **System Architecture Diagram**
> 
> A system architecture diagram is a visual blueprint of a system, mapping its core components, their relationships, and how data flows between them, acting as a shared, high-level guide for developers, architects, and stakeholders to understand structure, communication, and potential issues for planning and development. It's like a building's blueprint. The Attendance & Visitor Registration System uses a browser-based Blade frontend that communicates via Routes with Laravel Controllers responsible for attendance tracking, visitor registration, schedule management, and user administration, which in turn reads and writes data to a relational database (SQLite/MySQL) and stores visitor photos in system storage.

---

*Document Version: 1.0*  
*Last Updated: January 27, 2026*
