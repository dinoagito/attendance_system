/**
 * API Routes for Fingerprint Server
 * 
 * REST API endpoints for fingerprint operations
 */

const express = require('express');
const router = express.Router();
const FingerprintService = require('../services/ZKFingerprintService');
const DatabaseService = require('../services/DatabaseService');

/**
 * GET /api/status
 * Get scanner and server status
 */
router.get('/status', (req, res) => {
    const fingerprintService = FingerprintService.getInstance();
    res.json({
        success: true,
        server: 'online',
        scanner: fingerprintService.getDeviceStatus(),
        timestamp: new Date().toISOString()
    });
});

/**
 * POST /api/capture
 * Capture a fingerprint
 */
router.post('/capture', async (req, res) => {
    try {
        const fingerprintService = FingerprintService.getInstance();
        const result = await fingerprintService.captureFingerprint();
        
        // Broadcast to WebSocket clients
        if (global.broadcast) {
            global.broadcast({ type: 'fingerprint_captured', ...result });
        }
        
        res.json(result);
    } catch (error) {
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

/**
 * POST /api/verify
 * Verify fingerprint and record attendance
 */
router.post('/verify', async (req, res) => {
    try {
        const { template, allowAttendance, employeeId, preferSelectedEmployee } = req.body;
        const fingerprintService = FingerprintService.getInstance();
        const result = await fingerprintService.verifyFingerprint(template, {
            allowAttendance: allowAttendance === true,
            employeeId: employeeId ? parseInt(employeeId) : null,
            preferSelectedEmployee: preferSelectedEmployee !== false,
        });
        
        // Broadcast result to WebSocket clients
        if (global.broadcast) {
            global.broadcast({ type: 'verification_result', ...result });
        }
        
        res.json(result);
    } catch (error) {
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

/**
 * POST /api/enroll
 * Enroll a new fingerprint for an employee
 */
router.post('/enroll', async (req, res) => {
    try {
        const { employeeId, fingerIndex } = req.body;
        
        if (!employeeId) {
            return res.status(400).json({
                success: false,
                message: 'Employee ID is required'
            });
        }

        const fingerprintService = FingerprintService.getInstance();
        const result = await fingerprintService.enrollFingerprint(
            employeeId, 
            fingerIndex || 0
        );
        
        // Broadcast to WebSocket clients
        if (global.broadcast) {
            global.broadcast({ type: 'enrollment_result', ...result });
        }
        
        res.json(result);
    } catch (error) {
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

/**
 * DELETE /api/fingerprint/:employeeId
 * Delete fingerprint(s) for an employee
 */
router.delete('/fingerprint/:employeeId', async (req, res) => {
    try {
        const { employeeId } = req.params;
        const { fingerIndex } = req.query;
        
        const fingerprintService = FingerprintService.getInstance();
        const result = await fingerprintService.deleteFingerprint(
            employeeId,
            fingerIndex !== undefined ? parseInt(fingerIndex) : null
        );
        
        res.json(result);
    } catch (error) {
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

/**
 * GET /api/employees
 * Get all employees with fingerprint status
 */
router.get('/employees', async (req, res) => {
    try {
        const employees = await DatabaseService.query(`
            SELECT 
                e.id,
                CONCAT(e.first_name, ' ', e.last_name) as name,
                e.employee_id_number as employee_id,
                e.department,
                e.department as position,
                COUNT(ef.id) as fingerprint_count
            FROM employees e
            LEFT JOIN employee_fingerprints ef ON e.id = ef.employee_id AND ef.is_active = 1
            GROUP BY e.id
            ORDER BY e.first_name
        `);
        
        res.json({
            success: true,
            employees: employees
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

/**
 * GET /api/employees/:id/fingerprints
 * Get fingerprints for a specific employee
 */
router.get('/employees/:id/fingerprints', async (req, res) => {
    try {
        const { id } = req.params;
        
        const fingerprints = await DatabaseService.query(`
            SELECT 
                id,
                finger_index,
                quality_score,
                created_at,
                updated_at
            FROM employee_fingerprints
            WHERE employee_id = ? AND is_active = 1
            ORDER BY finger_index
        `, [id]);
        
        res.json({
            success: true,
            fingerprints: fingerprints
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

/**
 * GET /api/attendance/today
 * Get today's attendance records
 */
router.get('/attendance/today', async (req, res) => {
    try {
        const today = new Date().toISOString().split('T')[0];
        
        const attendance = await DatabaseService.query(`
            SELECT 
                a.id,
                a.employee_id,
                CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                e.employee_id_number as employee_code,
                e.department,
                a.date,
                a.time_in,
                a.time_out,
                a.time_in_2,
                a.time_out_2,
                a.time_in_3,
                a.time_out_3,
                a.time_in_4,
                a.time_out_4,
                a.status
            FROM employee_attendances a
            INNER JOIN employees e ON e.id = a.employee_id
            WHERE a.date = ?
            ORDER BY a.time_in DESC
        `, [today]);
        
        res.json({
            success: true,
            date: today,
            attendance: attendance
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

/**
 * GET /api/attendance/weekly
 * Get weekly attendance records
 */
router.get('/attendance/weekly', async (req, res) => {
    try {
        const weekly = await DatabaseService.query(`
            SELECT
                a.id,
                a.employee_id,
                DATE_FORMAT(a.date, '%Y-%m-%d') as date,
                e.employee_id_number as employee_code,
                CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                a.time_in,
                a.time_out,
                a.time_in_2,
                a.time_out_2,
                a.time_in_3,
                a.time_out_3,
                a.time_in_4,
                a.time_out_4
            FROM employee_attendances a
            INNER JOIN employees e ON e.id = a.employee_id
            WHERE a.date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            ORDER BY a.date DESC, e.employee_id_number ASC
        `);

        res.json({
            success: true,
            weekly: weekly
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

/**
 * POST /api/attendance/manual
 * Record attendance manually (for testing)
 */
router.post('/attendance/manual', async (req, res) => {
    try {
        const { employeeId, type } = req.body;
        
        if (!employeeId) {
            return res.status(400).json({
                success: false,
                message: 'Employee ID is required'
            });
        }

        const result = await DatabaseService.recordAttendance(employeeId, type || 'time_in');
        const attendance = await DatabaseService.getTodayAttendance(employeeId);
        
        res.json({
            success: true,
            ...result,
            attendance: attendance
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

module.exports = router;
