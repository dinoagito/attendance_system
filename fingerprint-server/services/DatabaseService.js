/**
 * Database Service
 * 
 * Handles MySQL database connections and queries
 * for fingerprint template storage and employee data.
 */

const mysql = require('mysql2/promise');

const TIME_IN_WINDOW_MINUTES = parseInt(process.env.TIME_IN_WINDOW_MINUTES || '240', 10);
const OVERTIME_MINUTES = parseInt(process.env.OVERTIME_MINUTES || '60', 10);
const UNDERTIME_MINUTES = parseInt(process.env.UNDERTIME_MINUTES || '60', 10);

class DatabaseService {
    static pool = null;

    /**
     * Initialize database connection pool
     */
    static async initialize() {
        if (this.pool) return this.pool;

        const useSSL = String(process.env.DB_SSL || '').toLowerCase() === 'true';

        this.pool = mysql.createPool({
            host: process.env.DB_HOST || 'localhost',
            port: process.env.DB_PORT || 3306,
            user: process.env.DB_USER || 'root',
            password: process.env.DB_PASSWORD || '',
            database: process.env.DB_NAME || 'attendance_app',
            waitForConnections: true,
            connectionLimit: 10,
            queueLimit: 0,
            connectTimeout: 15000,
            ...(useSSL ? { ssl: { rejectUnauthorized: false } } : {})
        });

        // Test connection
        const connection = await this.pool.getConnection();
        connection.release();
        
        return this.pool;
    }

    /**
     * Get database pool
     */
    static getPool() {
        if (!this.pool) {
            throw new Error('Database not initialized. Call initialize() first.');
        }
        return this.pool;
    }

    /**
     * Execute a query
     */
    static async query(sql, params = []) {
        const pool = this.getPool();
        const [results] = await pool.execute(sql, params);
        return results;
    }

    /**
     * Get all employees with fingerprint templates
     */
    static async getEmployeesWithFingerprints() {
        const sql = `
            SELECT 
                e.id,
                CONCAT(e.first_name, ' ', e.last_name) as name,
                e.employee_id_number as employee_id,
                e.department,
                e.department as position,
                ef.fingerprint_template,
                ef.finger_index,
                ef.quality_score
            FROM employees e
            INNER JOIN employee_fingerprints ef ON e.id = ef.employee_id
            WHERE ef.is_active = 1
            ORDER BY e.id, ef.finger_index
        `;
        return await this.query(sql);
    }

    /**
     * Get fingerprint templates for verification
     */
    static async getFingerprintTemplates() {
        const sql = `
            SELECT 
                ef.id as fingerprint_id,
                ef.employee_id,
                ef.fingerprint_template,
                ef.finger_index,
                CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                e.employee_id_number as employee_code,
                e.department,
                e.department as position
            FROM employee_fingerprints ef
            INNER JOIN employees e ON e.id = ef.employee_id
            WHERE ef.is_active = 1
        `;
        return await this.query(sql);
    }

    /**
     * Get fingerprint templates for a specific employee
     */
    static async getFingerprintTemplatesByEmployee(employeeId) {
        const sql = `
            SELECT 
                ef.id as fingerprint_id,
                ef.employee_id,
                ef.fingerprint_template,
                ef.finger_index,
                CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                e.employee_id_number as employee_code,
                e.department,
                e.department as position
            FROM employee_fingerprints ef
            INNER JOIN employees e ON e.id = ef.employee_id
            WHERE ef.is_active = 1
              AND ef.employee_id = ?
        `;
        return await this.query(sql, [employeeId]);
    }

    /**
     * Get employee by ID
     */
    static async getEmployeeById(employeeId) {
        const sql = `
            SELECT id, CONCAT(first_name, ' ', last_name) as name, employee_id_number as employee_id, department, department as position
            FROM employees
            WHERE id = ?
        `;
        const results = await this.query(sql, [employeeId]);
        return results[0] || null;
    }

    /**
     * Save fingerprint template
     */
    static async saveFingerprint(employeeId, template, fingerIndex = 0, qualityScore = 0) {
        // Check if fingerprint already exists for this finger
        const existing = await this.query(
            'SELECT id FROM employee_fingerprints WHERE employee_id = ? AND finger_index = ?',
            [employeeId, fingerIndex]
        );

        if (existing.length > 0) {
            // Update existing
            await this.query(
                `UPDATE employee_fingerprints 
                 SET fingerprint_template = ?, quality_score = ?, updated_at = NOW()
                 WHERE employee_id = ? AND finger_index = ?`,
                [template, qualityScore, employeeId, fingerIndex]
            );
            return { id: existing[0].id, updated: true };
        } else {
            // Insert new
            const result = await this.query(
                `INSERT INTO employee_fingerprints 
                 (employee_id, fingerprint_template, finger_index, quality_score, is_active, created_at, updated_at)
                 VALUES (?, ?, ?, ?, 1, NOW(), NOW())`,
                [employeeId, template, fingerIndex, qualityScore]
            );
            return { id: result.insertId, updated: false };
        }
    }

    /**
     * Delete fingerprint template
     */
    static async deleteFingerprint(employeeId, fingerIndex = null) {
        if (fingerIndex !== null) {
            await this.query(
                'DELETE FROM employee_fingerprints WHERE employee_id = ? AND finger_index = ?',
                [employeeId, fingerIndex]
            );
        } else {
            await this.query(
                'DELETE FROM employee_fingerprints WHERE employee_id = ?',
                [employeeId]
            );
        }
    }

    static formatDateLocal(dateObj) {
        const year = dateObj.getFullYear();
        const month = String(dateObj.getMonth() + 1).padStart(2, '0');
        const day = String(dateObj.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    static getDayName(dateObj) {
        return dateObj.toLocaleDateString('en-US', { weekday: 'long' });
    }

    static parseTimeToDate(dateString, timeString) {
        if (!timeString) {
            return null;
        }

        const [hourStr, minuteStr, secondStr] = String(timeString).split(':');
        const date = new Date(`${dateString}T00:00:00`);
        date.setHours(Number(hourStr || 0), Number(minuteStr || 0), Number(secondStr || 0), 0);
        return date;
    }

    static diffMinutes(startDate, endDate) {
        return Math.floor((endDate.getTime() - startDate.getTime()) / 60000);
    }

    static pickFirstTimeIn(record) {
        return record.time_in || record.time_in_2 || record.time_in_3 || record.time_in_4 || null;
    }

    static pickLastTimeOut(record) {
        return record.time_out_4 || record.time_out_3 || record.time_out_2 || record.time_out || null;
    }

    /**
     * Strict check: does employee have a schedule for the given date's weekday?
     * Respects both legacy day_of_week and new schedule_days JSON, no fallback.
     */
    static async hasScheduleForDate(employeeId, dateObj) {
        const dayName = this.getDayName(dateObj);
        const rows = await this.query(
            `SELECT id, day_of_week, schedule_days, schedule_group_key, start_time, end_time
             FROM employee_schedules
             WHERE employee_id = ?`,
            [employeeId]
        );

        for (const row of rows) {
            let days = [];
            if (row.schedule_days) {
                try {
                    const parsed = typeof row.schedule_days === 'string' ? JSON.parse(row.schedule_days) : row.schedule_days;
                    if (Array.isArray(parsed) && parsed.length > 0) {
                        days = parsed;
                    }
                } catch (e) {
                    days = [];
                }
            }
            if (days.length === 0 && row.day_of_week) {
                days = [row.day_of_week];
            }
            // Normalize comparison
            if (days.includes(dayName)) {
                return true;
            }
        }
        return false;
    }

    static async getEmployeeScheduleForDate(employeeId, dateObj) {
        const dayName = this.getDayName(dateObj);

        // Try strict day match first (including schedule_days JSON)
        const allRows = await this.query(
            `SELECT id, employee_id, day_of_week, schedule_days, start_time, end_time
             FROM employee_schedules
             WHERE employee_id = ?
             ORDER BY start_time ASC`,
            [employeeId]
        );

        for (const row of allRows) {
            let days = [];
            if (row.schedule_days) {
                try {
                    const parsed = typeof row.schedule_days === 'string' ? JSON.parse(row.schedule_days) : row.schedule_days;
                    if (Array.isArray(parsed) && parsed.length > 0) days = parsed;
                } catch (e) {}
            }
            if (days.length === 0 && row.day_of_week) days = [row.day_of_week];
            if (days.includes(dayName)) {
                return row;
            }
        }

        // No schedule for this weekday -> return null (strict, no generic fallback for validation)
        return null;
    }

    static evaluateTimeInStatus(schedule, scanDate, dateString) {
        if (!schedule || !schedule.start_time) {
            return {
                status: 'Present',
                allowTimeIn: true,
                lateMinutes: 0,
                message: 'Time-in recorded (no schedule assigned).',
            };
        }

        const scheduleStart = this.parseTimeToDate(dateString, schedule.start_time);
        const minutesLate = this.diffMinutes(scheduleStart, scanDate);

        if (minutesLate <= 0) {
            return {
                status: 'Present',
                allowTimeIn: true,
                lateMinutes: 0,
                message: 'Time-in recorded. Status: Present',
            };
        }

        if (minutesLate <= TIME_IN_WINDOW_MINUTES) {
            return {
                status: 'Late',
                allowTimeIn: true,
                lateMinutes: minutesLate,
                message: `Time-in recorded. Status: Late (${minutesLate} minute${minutesLate === 1 ? '' : 's'})`,
            };
        }

        return {
            status: 'Absent',
            allowTimeIn: false,
            lateMinutes: minutesLate,
            message: `Marked Absent. Time-in window exceeded by ${minutesLate} minute${minutesLate === 1 ? '' : 's'}.`,
        };
    }

    static computeAttendanceEvaluation(record, schedule, dateString) {
        const firstIn = this.pickFirstTimeIn(record);
        const lastOut = this.pickLastTimeOut(record);

        if (!firstIn) {
            return {
                totalWorkingHours: 0,
                totalWorkingMinutes: 0,
                extraMinutes: 0,
                missingMinutes: 0,
                isOvertime: false,
                isExtension: false,
                isUndertime: false,
                scheduledStart: schedule?.start_time || null,
                scheduledEnd: schedule?.end_time || null,
            };
        }

        const firstInDate = this.parseTimeToDate(dateString, firstIn);
        const lastOutDate = lastOut ? this.parseTimeToDate(dateString, lastOut) : null;

        let totalWorkingMinutes = 0;
        if (lastOutDate && lastOutDate >= firstInDate) {
            totalWorkingMinutes = this.diffMinutes(firstInDate, lastOutDate);
        }

        let extraMinutes = 0;
        let missingMinutes = 0;

        if (schedule && schedule.end_time && lastOutDate) {
            const scheduleEndDate = this.parseTimeToDate(dateString, schedule.end_time);
            const delta = this.diffMinutes(scheduleEndDate, lastOutDate);

            if (delta > 0) {
                extraMinutes = delta;
            } else if (delta < 0) {
                missingMinutes = Math.abs(delta);
            }
        }

        return {
            totalWorkingHours: Number((totalWorkingMinutes / 60).toFixed(2)),
            totalWorkingMinutes,
            extraMinutes,
            missingMinutes,
            isOvertime: extraMinutes >= OVERTIME_MINUTES,
            isExtension: extraMinutes > 0 && extraMinutes < OVERTIME_MINUTES,
            isUndertime: missingMinutes >= UNDERTIME_MINUTES,
            scheduledStart: schedule?.start_time || null,
            scheduledEnd: schedule?.end_time || null,
        };
    }

    /**
     * Record attendance
     */
    static async recordAttendance(employeeId, type = 'time_in') {
        const now = new Date();
        const today = this.formatDateLocal(now);
        const schedule = await this.getEmployeeScheduleForDate(employeeId, now);

        // Schedule validation: reject if employee has no schedule for today (dynamic date, not hardcoded)
        if (!schedule) {
            const dayName = this.getDayName(now);
            return {
                action: 'no_schedule',
                message: `Attendance rejected — no schedule for today (${dayName}, ${today}).`,
                recordId: null,
                status: null,
                evaluation: null,
                schedule_valid: false,
            };
        }

        const timeInDecision = this.evaluateTimeInStatus(schedule, now, today);
        
        // Check if attendance record exists for today
        const existing = await this.query(
            `SELECT id, time_in, time_out, time_in_2, time_out_2, time_in_3, time_out_3, time_in_4, time_out_4
             FROM employee_attendances
             WHERE employee_id = ? AND date = ?
             ORDER BY id DESC
             LIMIT 1`,
            [employeeId, today]
        );

        if (existing.length > 0) {
            const record = existing[0];
            
            if (!record.time_in) {
                if (!timeInDecision.allowTimeIn) {
                    await this.query(
                        `UPDATE employee_attendances
                         SET status = 'Absent', updated_at = NOW()
                         WHERE id = ?`,
                        [record.id]
                    );

                    const todayAttendance = await this.getTodayAttendance(employeeId);
                    return {
                        action: 'absent_marked',
                        message: timeInDecision.message,
                        recordId: record.id,
                        status: 'Absent',
                        evaluation: todayAttendance?.evaluation || null,
                    };
                }

                await this.query(
                    `UPDATE employee_attendances
                     SET time_in = CURTIME(), status = ?, updated_at = NOW()
                     WHERE id = ?`,
                    [timeInDecision.status, record.id]
                );

                const todayAttendance = await this.getTodayAttendance(employeeId);
                return { 
                    action: 'time_in_1', 
                    message: timeInDecision.message,
                    recordId: record.id,
                    status: timeInDecision.status,
                    evaluation: todayAttendance?.evaluation || null,
                };
            } else if (!record.time_out) {
                await this.query(
                    'UPDATE employee_attendances SET time_out = CURTIME(), updated_at = NOW() WHERE id = ?',
                    [record.id]
                );

                const todayAttendance = await this.getTodayAttendance(employeeId);
                const evalData = todayAttendance?.evaluation || null;

                return { 
                    action: 'time_out_1', 
                    message: 'Time-out recorded',
                    recordId: record.id,
                    status: todayAttendance?.status || 'Present',
                    evaluation: evalData,
                };
            } else if (!record.time_in_2) {
                await this.query(
                    'UPDATE employee_attendances SET time_in_2 = CURTIME(), updated_at = NOW() WHERE id = ?',
                    [record.id]
                );

                const todayAttendance = await this.getTodayAttendance(employeeId);
                return {
                    action: 'time_in_2',
                    message: '2nd time-in recorded',
                    recordId: record.id,
                    status: todayAttendance?.status || 'Present',
                    evaluation: todayAttendance?.evaluation || null,
                };
            } else if (!record.time_out_2) {
                await this.query(
                    'UPDATE employee_attendances SET time_out_2 = CURTIME(), updated_at = NOW() WHERE id = ?',
                    [record.id]
                );

                const todayAttendance = await this.getTodayAttendance(employeeId);
                return {
                    action: 'time_out_2',
                    message: '2nd time-out recorded',
                    recordId: record.id,
                    status: todayAttendance?.status || 'Present',
                    evaluation: todayAttendance?.evaluation || null,
                };
            } else if (!record.time_in_3) {
                await this.query(
                    'UPDATE employee_attendances SET time_in_3 = CURTIME(), updated_at = NOW() WHERE id = ?',
                    [record.id]
                );

                const todayAttendance = await this.getTodayAttendance(employeeId);
                return {
                    action: 'time_in_3',
                    message: '3rd time-in recorded',
                    recordId: record.id,
                    status: todayAttendance?.status || 'Present',
                    evaluation: todayAttendance?.evaluation || null,
                };
            } else if (!record.time_out_3) {
                await this.query(
                    'UPDATE employee_attendances SET time_out_3 = CURTIME(), updated_at = NOW() WHERE id = ?',
                    [record.id]
                );

                const todayAttendance = await this.getTodayAttendance(employeeId);
                return {
                    action: 'time_out_3',
                    message: '3rd time-out recorded',
                    recordId: record.id,
                    status: todayAttendance?.status || 'Present',
                    evaluation: todayAttendance?.evaluation || null,
                };
            } else if (!record.time_in_4) {
                await this.query(
                    'UPDATE employee_attendances SET time_in_4 = CURTIME(), updated_at = NOW() WHERE id = ?',
                    [record.id]
                );

                const todayAttendance = await this.getTodayAttendance(employeeId);
                return {
                    action: 'time_in_4',
                    message: '4th time-in recorded',
                    recordId: record.id,
                    status: todayAttendance?.status || 'Present',
                    evaluation: todayAttendance?.evaluation || null,
                };
            } else if (!record.time_out_4) {
                await this.query(
                    'UPDATE employee_attendances SET time_out_4 = CURTIME(), updated_at = NOW() WHERE id = ?',
                    [record.id]
                );

                const todayAttendance = await this.getTodayAttendance(employeeId);
                return {
                    action: 'time_out_4',
                    message: '4th time-out recorded',
                    recordId: record.id,
                    status: todayAttendance?.status || 'Present',
                    evaluation: todayAttendance?.evaluation || null,
                };
            } else {
                const todayAttendance = await this.getTodayAttendance(employeeId);
                return { 
                    action: 'max_reached', 
                    message: 'Maximum of 4 time-in/time-out pairs reached for today',
                    recordId: record.id,
                    status: todayAttendance?.status || 'Present',
                    evaluation: todayAttendance?.evaluation || null,
                };
            }
        } else {
            if (!timeInDecision.allowTimeIn) {
                const result = await this.query(
                    `INSERT INTO employee_attendances (employee_id, date, status, created_at, updated_at)
                     VALUES (?, ?, 'Absent', NOW(), NOW())`,
                    [employeeId, today]
                );

                const todayAttendance = await this.getTodayAttendance(employeeId);
                return {
                    action: 'absent_marked',
                    message: timeInDecision.message,
                    recordId: result.insertId,
                    status: 'Absent',
                    evaluation: todayAttendance?.evaluation || null,
                };
            }

            // Create new attendance record with time_in
            const result = await this.query(
                `INSERT INTO employee_attendances (employee_id, date, time_in, status, created_at, updated_at)
                 VALUES (?, ?, CURTIME(), ?, NOW(), NOW())`,
                [employeeId, today, timeInDecision.status]
            );

            const todayAttendance = await this.getTodayAttendance(employeeId);
            return { 
                action: 'time_in_1', 
                message: timeInDecision.message,
                recordId: result.insertId,
                status: timeInDecision.status,
                evaluation: todayAttendance?.evaluation || null,
            };
        }
    }

    /**
     * Get today's attendance for an employee
     */
    static async getTodayAttendance(employeeId) {
        const now = new Date();
        const today = this.formatDateLocal(now);
        const sql = `
            SELECT id, date, time_in, time_out, time_in_2, time_out_2, time_in_3, time_out_3, time_in_4, time_out_4, status
            FROM employee_attendances
            WHERE employee_id = ? AND date = ?
            ORDER BY id DESC
            LIMIT 1
        `;
        const results = await this.query(sql, [employeeId, today]);
        if (results.length === 0) {
            return null;
        }

        const schedule = await this.getEmployeeScheduleForDate(employeeId, now);
        const evaluation = this.computeAttendanceEvaluation(results[0], schedule, today);

        return {
            ...results[0],
            evaluation,
        };
    }

    /**
     * Close database connection
     */
    static async close() {
        if (this.pool) {
            await this.pool.end();
            this.pool = null;
        }
    }
}

module.exports = DatabaseService;
