# Data Flow Diagram (DFD) Level 0 - Context Diagram

## Overview

This section presents the graphical representations of the Web-based Attendance and Visitor Registration System to illustrate its overall structure, data flow, and interactions among users and system components. The diagrams provide a clear visual understanding of how the system operates and how information is processed within the system. The diagrams included in this section are the Context Diagram (DFD Level 0), Entity Relationship Diagram (ERD), and System Architecture Diagram, which collectively describe the system boundaries, database structure, and technical design of the proposed system.

---

## 📊 DFD Level 0 (Context Diagram)

The Context Diagram (DFD Level 0) provides a high-level overview of the **Attendance and Visitor Registration System**, showing the system as a single process and its interactions with external entities. It identifies the main data flows between the system and its users.

### Diagram

```
                                    ┌─────────────────┐
                                    │  Administrator  │
                                    └────────┬────────┘
                                             │
                                   Reports   │   User Data
                                             │   Schedule Data
                                             ▼
                          ┌──────────────────────────────────┐
      Student List        │                                  │
    ◄─────────────────────┤                                  │
                          │                                  │
      Schedule List       │        Attendance and            │        Visitor
    ◄─────────────────────┤           Visitor                ├──────────────────►
                          │        Registration              │        Confirmation
┌──────────┐              │           System                 │              ┌──────────┐
│          │  Attendance  │                                  │  Visitor     │          │
│ Student/ │  Data        │                                  │  Info        │  Visitor │
│ Employee ├─────────────►│                                  │◄─────────────┤          │
│          │              │                                  │              └──────────┘
└──────────┘              └──────────────────────────────────┘
```

---

## 📋 External Entities

| Entity | Description |
|--------|-------------|
| **Administrator** | System administrator who manages users, schedules, and system configurations. Has full access to all system features including reports and data management. |
| **Student** | Students who use RFID cards or ID scanning to record their attendance (time-in and time-out). Can view their attendance records. |
| **Employee** | Staff members who scan their RFID cards or IDs to log their attendance. Subject to schedule management and attendance tracking. |
| **Visitor** | External individuals who register at the facility, have their photos captured, and check in/out of the premises. |

---

## 📥 Data Flows (Inputs to the System)

| From Entity | Data Flow | Description |
|-------------|-----------|-------------|
| Administrator | User Credentials | Login information to access the system |
| Administrator | User Data | Information about students, employees (name, ID, contact) |
| Administrator | Schedule Requests | Work/class schedule creation and modifications |
| Administrator | System Configuration | System settings and preferences |
| Student | RFID/ID Scan | Student identification via RFID card or barcode |
| Student | Time-in/Time-out Request | Attendance logging request |
| Employee | RFID/ID Scan | Employee identification via RFID card or barcode |
| Employee | Time-in/Time-out Request | Attendance logging request |
| Visitor | Registration Data | Name, phone, purpose of visit, person to visit |
| Visitor | Photo | Captured photo via webcam or uploaded image |
| Visitor | Check-in/Check-out Request | Visitor entry and exit logging |

---

## 📤 Data Flows (Outputs from the System)

| To Entity | Data Flow | Description |
|-----------|-----------|-------------|
| Administrator | Reports | Attendance summaries, visitor logs, system statistics |
| Administrator | User Management Data | Current user listings and status |
| Administrator | Schedule Data | Current schedule configurations |
| Student | Attendance Records | Personal attendance history and calendar view |
| Student | Status | Current attendance status (present, late, absent) |
| Employee | Attendance Records | Personal attendance history |
| Employee | Status | Current check-in/check-out status |
| Visitor | Visitor Pass/Receipt | Registration confirmation with check-in time |
| Visitor | Check-out Confirmation | Exit timestamp and visit duration |

---

## 🔄 Process Description

The **Attendance and Visitor Registration System** is the central process that:

1. **Authenticates** administrators and validates user access
2. **Processes** RFID/ID scans from students and employees
3. **Records** time-in and time-out events with timestamps
4. **Manages** visitor registrations including photo capture
5. **Tracks** visitor check-in and check-out activities
6. **Generates** attendance reports and visitor logs
7. **Maintains** schedules for employees and students
8. **Stores** all data in the database for retrieval and reporting

---

## 📊 Diagram File (Draw.io Format)

A Draw.io compatible diagram file has been created:

**File:** `docs/dfd_level0_context_diagram.drawio`

### How to Open and Edit

1. **Option 1: Draw.io Online (Recommended)**
   - Go to [https://app.diagrams.net/](https://app.diagrams.net/)
   - Click "Open Existing Diagram"
   - Select the file `dfd_level0_context_diagram.drawio`
   - Edit and export as PNG, PDF, or other formats

2. **Option 2: VS Code Extension**
   - Install "Draw.io Integration" extension in VS Code
   - Open the `.drawio` file directly in VS Code

3. **Option 3: Desktop App**
   - Download Draw.io desktop from [https://www.drawio.com/](https://www.drawio.com/)
   - Open the file

---

## 📝 Summary Table

| Component | Count | Examples |
|-----------|-------|----------|
| External Entities | 4 | Administrator, Student, Employee, Visitor |
| Input Data Flows | 10 | RFID scans, registration data, user data, etc. |
| Output Data Flows | 9 | Attendance records, reports, confirmations, etc. |
| Central Process | 1 | Attendance and Visitor Registration System |

---

## 🎯 Key Insights from DFD Level 0

- The system serves **four distinct user types** with different interaction patterns
- **Real-time data capture** through RFID scanning and webcam photo capture
- **Bidirectional data flow** for all entities (input and output)
- **Centralized processing** with single point of data management
- **Multiple output formats** including reports, records, and confirmations
