# ZKTeco ZK9500 Fingerprint Server

Node.js server for integrating ZKTeco ZK9500 USB fingerprint scanner with the Attendance Monitoring System.

## Features

- Real-time fingerprint capture via WebSocket
- Fingerprint enrollment for employees
- Fingerprint verification for attendance
- REST API for integration
- Simulation mode for development (works without physical scanner)

## Prerequisites

1. **Node.js** (v16 or higher)
2. **ZKTeco ZK9500** fingerprint scanner (optional for development)
3. **ZKTeco SDK** (optional - required for real scanner)
4. **MySQL** database

## Installation

### 1. Install Dependencies

```bash
cd fingerprint-server
npm install
```

### 2. Configure Environment

```bash
# Copy example config
copy .env.example .env

# Edit .env with your settings
notepad .env
```

### 3. Configure Database

Make sure your `.env` has correct MySQL credentials:

```env
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASSWORD=
DB_NAME=attendance_app
```

### 4. Run Laravel Migration

From the main Laravel project directory:

```bash
php artisan migrate
```

This creates the `employee_fingerprints` table.

### 5. Start the Server

```bash
# Development mode (with auto-reload)
npm run dev

# Production mode
npm start
```

## API Endpoints

### REST API (http://localhost:3001/api)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/status` | Get server and scanner status |
| POST | `/capture` | Capture a fingerprint |
| POST | `/verify` | Verify fingerprint and record attendance |
| POST | `/enroll` | Enroll fingerprint for an employee |
| DELETE | `/fingerprint/:employeeId` | Delete employee fingerprints |
| GET | `/employees` | Get all employees with fingerprint status |
| GET | `/employees/:id/fingerprints` | Get fingerprints for an employee |
| GET | `/attendance/today` | Get today's attendance records |

### WebSocket (ws://localhost:3001/ws)

Connect to receive real-time updates. Send JSON messages:

```javascript
// Start verification
{ "type": "verify" }

// Enroll fingerprint
{ "type": "enroll", "employeeId": 1, "fingerIndex": 6 }

// Get scanner status
{ "type": "scanner_status" }
```

## Database Schema

### employee_fingerprints Table

```sql
CREATE TABLE employee_fingerprints (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    employee_id BIGINT NOT NULL,
    fingerprint_template LONGTEXT NOT NULL,
    finger_index TINYINT DEFAULT 0,
    quality_score INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);
```

### Finger Index Reference

| Index | Finger |
|-------|--------|
| 0 | Left Thumb |
| 1 | Left Index |
| 2 | Left Middle |
| 3 | Left Ring |
| 4 | Left Pinky |
| 5 | Right Thumb |
| 6 | Right Index |
| 7 | Right Middle |
| 8 | Right Ring |
| 9 | Right Pinky |

## System Flow

### Attendance Verification

```
1. Employee places finger on scanner
2. Node.js captures fingerprint template
3. Template compared against database
4. If matched:
   - Employee info retrieved
   - Attendance recorded (time_in or time_out)
   - Result sent to web interface
5. If not matched:
   - "No Match" message displayed
```

### Fingerprint Enrollment

```
1. Admin selects employee from list
2. Admin selects finger (default: Right Index)
3. Employee places finger on scanner
4. Fingerprint captured and stored in database
5. Employee can now use biometric attendance
```

## Simulation Mode

When the ZK9500 scanner is not connected or SDK is not installed, the server runs in **simulation mode**:

- Generates random fingerprint templates
- Returns random match scores
- Perfect for development and testing

To test with simulation:

1. Start the server without the scanner connected
2. The server will log: "Running in simulation mode"
3. Use the web interface normally

## Troubleshooting

### Scanner Not Detected

1. Ensure ZK9500 is properly connected via USB
2. Install ZKTeco device drivers
3. Check Device Manager for the scanner
4. Restart the Node.js server

### Database Connection Error

1. Verify MySQL is running
2. Check credentials in `.env`
3. Ensure `attendance_app` database exists
4. Run Laravel migrations

### FFI Errors

The SDK integration uses FFI (Foreign Function Interface). If you encounter errors:

1. Install Visual C++ Redistributable
2. Ensure ZKTeco SDK DLLs are in the correct path
3. Update `ZKTECO_SDK_PATH` in `.env`

## Integration with Laravel

### Add Route for Enrollment Page

In `routes/web.php`:

```php
Route::get('/fingerprint/enroll', function () {
    return view('fingerprint.enroll');
})->name('fingerprint.enroll');
```

### Update Navigation (optional)

Add link to enrollment page in your sidebar/navigation.

## Security Considerations

1. **Template Storage**: Fingerprint templates are stored as Base64-encoded binary data
2. **No Image Storage**: Raw fingerprint images are not stored, only mathematical templates
3. **One-Way**: Templates cannot be reverse-engineered to recreate fingerprints
4. **Local Processing**: All fingerprint processing happens locally

## License

MIT License
