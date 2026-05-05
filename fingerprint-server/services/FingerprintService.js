/**
 * Fingerprint Service
 * 
 * Handles communication with ZKTeco ZK9500 fingerprint scanner
 * including capture, enrollment, and verification.
 * 
 * Note: This service uses the ZKTeco SDK via FFI (Foreign Function Interface).
 * Make sure the ZKTeco SDK DLLs are installed and accessible.
 */

const path = require('path');
const DatabaseService = require('./DatabaseService');

// Fingerprint matching threshold (0-100, higher = stricter)
const MATCH_THRESHOLD = parseInt(process.env.FINGERPRINT_THRESHOLD) || 50;

class FingerprintService {
    static instance = null;
    
    constructor() {
        this.deviceHandle = null;
        this.isInitialized = false;
        this.isSimulationMode = false;
        this.zkfp = null;
        this.lastCapturedTemplate = null;
    }

    /**
     * Get singleton instance
     */
    static getInstance() {
        if (!this.instance) {
            this.instance = new FingerprintService();
        }
        return this.instance;
    }

    /**
     * Initialize the fingerprint scanner
     */
    async initialize() {
        try {
            // Try to load ZKTeco SDK
            const sdkLoaded = await this.loadSDK();
            
            if (!sdkLoaded) {
                console.log('⚠️  ZKTeco SDK not found. Running in simulation mode.');
                this.isSimulationMode = true;
                this.isInitialized = true;
                return { success: true, message: 'Running in simulation mode', simulationMode: true };
            }

            // Initialize device
            const initResult = this.zkfp.Init();
            if (initResult !== 0) {
                throw new Error(`Failed to initialize SDK. Error code: ${initResult}`);
            }

            // Get device count
            const deviceCount = this.zkfp.GetDeviceCount();
            if (deviceCount === 0) {
                this.zkfp.Terminate();
                console.log('⚠️  No fingerprint scanner detected. Running in simulation mode.');
                this.isSimulationMode = true;
                this.isInitialized = true;
                return { success: true, message: 'No scanner detected, simulation mode', simulationMode: true };
            }

            // Open device
            this.deviceHandle = this.zkfp.OpenDevice(0);
            if (!this.deviceHandle) {
                this.zkfp.Terminate();
                throw new Error('Failed to open fingerprint scanner device');
            }

            this.isInitialized = true;
            console.log(`✅ ZK9500 Scanner initialized. Devices found: ${deviceCount}`);
            
            return { 
                success: true, 
                message: 'Scanner initialized successfully',
                deviceCount: deviceCount,
                simulationMode: false
            };

        } catch (error) {
            console.error('Fingerprint initialization error:', error);
            this.isSimulationMode = true;
            this.isInitialized = true;
            return { 
                success: true, 
                message: `Simulation mode: ${error.message}`,
                simulationMode: true
            };
        }
    }

    /**
     * Load ZKTeco SDK
     */
    async loadSDK() {
        try {
            const ffi = require('ffi-napi');
            const ref = require('ref-napi');

            const sdkPath = process.env.ZKTECO_SDK_PATH || 'C:/Program Files/ZKTeco/ZK9500/';
            const dllPath = path.join(sdkPath, 'libzkfp.dll');

            // Define FFI interface for ZKTeco SDK
            this.zkfp = ffi.Library(dllPath, {
                'ZKFPM_Init': ['int', []],
                'ZKFPM_Terminate': ['int', []],
                'ZKFPM_GetDeviceCount': ['int', []],
                'ZKFPM_OpenDevice': ['pointer', ['int']],
                'ZKFPM_CloseDevice': ['int', ['pointer']],
                'ZKFPM_AcquireFingerprint': ['int', ['pointer', 'pointer', 'int', 'pointer', 'pointer']],
                'ZKFPM_DBMatch': ['int', ['pointer', 'pointer', 'int', 'pointer', 'int']],
                'ZKFPM_DBAdd': ['int', ['pointer', 'int', 'pointer', 'int']],
                'ZKFPM_DBDel': ['int', ['pointer', 'int']],
                'ZKFPM_DBClear': ['int', ['pointer']],
                'ZKFPM_DBCount': ['int', ['pointer']],
                'ZKFPM_ExtractFromImage': ['int', ['pointer', 'string', 'int', 'int', 'pointer', 'pointer']],
                'ZKFPM_GenRegTemplate': ['int', ['pointer', 'pointer', 'pointer', 'pointer', 'pointer', 'int']],
                'ZKFPM_DBIdentify': ['int', ['pointer', 'pointer', 'int', 'pointer', 'pointer']]
            });

            // Create shorthand methods
            this.zkfp.Init = () => this.zkfp.ZKFPM_Init();
            this.zkfp.Terminate = () => this.zkfp.ZKFPM_Terminate();
            this.zkfp.GetDeviceCount = () => this.zkfp.ZKFPM_GetDeviceCount();
            this.zkfp.OpenDevice = (index) => this.zkfp.ZKFPM_OpenDevice(index);
            this.zkfp.CloseDevice = (handle) => this.zkfp.ZKFPM_CloseDevice(handle);

            return true;
        } catch (error) {
            console.log('SDK load error:', error.message);
            return false;
        }
    }

    /**
     * Get device status
     */
    getDeviceStatus() {
        return {
            initialized: this.isInitialized,
            connected: this.deviceHandle !== null || this.isSimulationMode,
            simulationMode: this.isSimulationMode
        };
    }

    /**
     * Capture fingerprint from scanner
     */
    async captureFingerprint() {
        if (!this.isInitialized) {
            throw new Error('Fingerprint scanner not initialized');
        }

        // Simulation mode - generate fake template
        if (this.isSimulationMode) {
            return this.simulateCapture();
        }

        return new Promise((resolve, reject) => {
            try {
                const ref = require('ref-napi');
                const templateSize = 2048;
                const templateBuffer = Buffer.alloc(templateSize);
                const sizePtr = ref.alloc('int', templateSize);
                const imageBuffer = Buffer.alloc(640 * 480); // Standard fingerprint image size

                // Capture fingerprint
                const result = this.zkfp.ZKFPM_AcquireFingerprint(
                    this.deviceHandle,
                    imageBuffer,
                    640 * 480,
                    templateBuffer,
                    sizePtr
                );

                if (result === 0) {
                    const actualSize = sizePtr.deref();
                    const template = templateBuffer.slice(0, actualSize).toString('base64');
                    
                    this.lastCapturedTemplate = template;
                    
                    resolve({
                        success: true,
                        template: template,
                        quality: this.calculateQuality(templateBuffer, actualSize),
                        message: 'Fingerprint captured successfully'
                    });
                } else {
                    reject(new Error(`Capture failed. Error code: ${result}`));
                }
            } catch (error) {
                reject(error);
            }
        });
    }

    /**
     * Simulate fingerprint capture for development
     */
    simulateCapture() {
        // Generate a random template for simulation
        const simulatedTemplate = Buffer.alloc(512);
        for (let i = 0; i < 512; i++) {
            simulatedTemplate[i] = Math.floor(Math.random() * 256);
        }
        
        const template = simulatedTemplate.toString('base64');
        this.lastCapturedTemplate = template;

        return {
            success: true,
            template: template,
            quality: 80 + Math.floor(Math.random() * 20),
            message: 'Fingerprint captured (simulation mode)',
            simulationMode: true
        };
    }

    /**
     * Verify fingerprint against database
     */
    async verifyFingerprint(capturedTemplate = null) {
        if (!this.isInitialized) {
            throw new Error('Fingerprint scanner not initialized');
        }

        // Use provided template or capture new one
        let templateToVerify = capturedTemplate || this.lastCapturedTemplate;
        
        if (!templateToVerify) {
            const captureResult = await this.captureFingerprint();
            templateToVerify = captureResult.template;
        }

        // Get all fingerprint templates from database
        const storedTemplates = await DatabaseService.getFingerprintTemplates();

        if (storedTemplates.length === 0) {
            return {
                success: false,
                matched: false,
                message: 'No fingerprints enrolled in system'
            };
        }

        // Find matching template
        const matchResult = await this.findMatch(templateToVerify, storedTemplates);

        if (matchResult.matched) {
            // Record attendance
            const attendanceResult = await DatabaseService.recordAttendance(matchResult.employee.employee_id);
            const todayAttendance = await DatabaseService.getTodayAttendance(matchResult.employee.employee_id);

            return {
                success: true,
                matched: true,
                employee: matchResult.employee,
                matchScore: matchResult.score,
                attendance: {
                    ...attendanceResult,
                    record: todayAttendance
                },
                message: `Welcome, ${matchResult.employee.employee_name}! ${attendanceResult.message}`
            };
        }

        return {
            success: true,
            matched: false,
            message: 'Fingerprint not recognized. Please try again or contact administrator.'
        };
    }

    /**
     * Find matching fingerprint in stored templates
     */
    async findMatch(capturedTemplate, storedTemplates) {
        if (this.isSimulationMode) {
            return {
                matched: false,
                employee: null,
                score: 0
            };
        }

        let bestMatch = null;
        let bestScore = 0;

        const capturedBuffer = Buffer.from(capturedTemplate, 'base64');

        for (const stored of storedTemplates) {
            const score = await this.compareTemplates(capturedBuffer, Buffer.from(stored.fingerprint_template, 'base64'));

            if (score > bestScore && score >= MATCH_THRESHOLD) {
                bestScore = score;
                bestMatch = {
                    employee_id: stored.employee_id,
                    employee_name: stored.employee_name,
                    employee_code: stored.employee_code,
                    department: stored.department,
                    position: stored.position
                };
            }
        }

        return {
            matched: bestMatch !== null,
            employee: bestMatch,
            score: bestScore
        };
    }

    /**
     * Compare two fingerprint templates using SDK
     */
    async compareTemplates(template1, template2) {
        if (this.isSimulationMode) {
            return Math.floor(Math.random() * 100);
        }

        try {
            const ref = require('ref-napi');
            const scorePtr = ref.alloc('int', 0);

            const result = this.zkfp.ZKFPM_DBMatch(
                this.deviceHandle,
                template1,
                template1.length,
                template2,
                template2.length
            );

            if (result >= 0) {
                return result; // Match score
            }
            return 0;
        } catch (error) {
            console.error('Template comparison error:', error);
            return 0;
        }
    }

    /**
     * Enroll fingerprint for an employee
     */
    async enrollFingerprint(employeeId, fingerIndex = 0) {
        if (!this.isInitialized) {
            throw new Error('Fingerprint scanner not initialized');
        }

        // Verify employee exists
        const employee = await DatabaseService.getEmployeeById(employeeId);
        if (!employee) {
            throw new Error('Employee not found');
        }

        // Capture fingerprint
        console.log(`📌 Enrolling fingerprint for employee: ${employee.name}`);
        const captureResult = await this.captureFingerprint();

        if (!captureResult.success) {
            throw new Error('Failed to capture fingerprint');
        }

        // Save to database
        const saveResult = await DatabaseService.saveFingerprint(
            employeeId,
            captureResult.template,
            fingerIndex,
            captureResult.quality
        );

        return {
            success: true,
            employee: employee,
            fingerIndex: fingerIndex,
            quality: captureResult.quality,
            updated: saveResult.updated,
            message: saveResult.updated 
                ? `Fingerprint updated for ${employee.name}`
                : `Fingerprint enrolled for ${employee.name}`
        };
    }

    /**
     * Delete enrolled fingerprint
     */
    async deleteFingerprint(employeeId, fingerIndex = null) {
        await DatabaseService.deleteFingerprint(employeeId, fingerIndex);
        return {
            success: true,
            message: fingerIndex !== null 
                ? `Fingerprint ${fingerIndex} deleted` 
                : 'All fingerprints deleted for employee'
        };
    }

    /**
     * Calculate fingerprint quality score
     */
    calculateQuality(buffer, size) {
        // Simple quality estimation based on template data variance
        let sum = 0;
        for (let i = 0; i < Math.min(size, 256); i++) {
            sum += buffer[i];
        }
        const avg = sum / Math.min(size, 256);
        
        let variance = 0;
        for (let i = 0; i < Math.min(size, 256); i++) {
            variance += Math.pow(buffer[i] - avg, 2);
        }
        variance /= Math.min(size, 256);
        
        // Normalize to 0-100 scale
        return Math.min(100, Math.max(0, Math.floor(Math.sqrt(variance) * 2)));
    }

    /**
     * Cleanup and close device
     */
    async cleanup() {
        try {
            if (this.deviceHandle && !this.isSimulationMode) {
                this.zkfp.CloseDevice(this.deviceHandle);
                this.zkfp.Terminate();
            }
            this.deviceHandle = null;
            this.isInitialized = false;
            console.log('✅ Fingerprint scanner cleaned up');
        } catch (error) {
            console.error('Cleanup error:', error);
        }
    }
}

module.exports = FingerprintService;
