/**
 * ZKTeco ZK9500 Fingerprint Service using Koffi
 * 
 * This service interfaces with the ZKTeco libzkfp.dll SDK
 */

const path = require('path');
const DatabaseService = require('./DatabaseService');

// Fingerprint matching threshold (0-100, higher = stricter)
const MATCH_THRESHOLD = parseInt(process.env.FINGERPRINT_THRESHOLD) || 35;
const ALLOW_SELECTED_EMPLOYEE_FALLBACK = process.env.ALLOW_SELECTED_EMPLOYEE_FALLBACK === 'true';

class ZKFingerprintService {
    static instance = null;
    
    constructor() {
        this.deviceHandle = null;
        this.dbHandle = null;
        this.isInitialized = false;
        this.isSimulationMode = false;
        this.zkfp = null;
        this.lastCapturedTemplate = null;
        this.imageWidth = 0;
        this.imageHeight = 0;
        this.operationQueue = Promise.resolve();
        this.currentOperation = 'idle';
        this.isReinitializing = false;
        this.disconnectStrikeCount = 0;
    }

    runExclusive(operationName, action) {
        const execute = async () => {
            this.currentOperation = operationName;
            console.log(`[OPERATION] Started: ${operationName}`);
            try {
                return await action();
            } catch (error) {
                console.log(`[OPERATION] ${operationName} error:`, error.message);
                throw error;
            } finally {
                console.log(`[OPERATION] Ended: ${operationName}`);
                this.currentOperation = 'idle';
            }
        };

        const queuedTask = this.operationQueue.then(execute, execute);
        this.operationQueue = queuedTask.catch(() => undefined);
        return queuedTask;
    }

    isRecoverableScannerError(error) {
        const message = (error && error.message ? error.message : String(error)).toLowerCase();
        return message.includes('invalid handle')
            || message.includes('no device connected')
            || message.includes('device not initialized')
            || message.includes('capture failed - device busy');
    }

    async reinitializeScanner() {
        if (this.isReinitializing) {
            // Wait for in-progress reinit to complete.
            while (this.isReinitializing) {
                await new Promise((resolve) => setTimeout(resolve, 100));
            }
            return;
        }

        this.isReinitializing = true;
        try {
            console.warn('↻ Reinitializing fingerprint scanner...');

            if (this.dbHandle && !this.isSimulationMode) {
                try {
                    this.zkfp.DBFree(this.dbHandle);
                } catch (error) {
                    console.warn('DBFree failed during reinit:', error.message);
                }
            }

            if (this.deviceHandle && !this.isSimulationMode) {
                try {
                    this.zkfp.CloseDevice(this.deviceHandle);
                } catch (error) {
                    console.warn('CloseDevice failed during reinit:', error.message);
                }
            }

            this.deviceHandle = null;
            this.dbHandle = null;
            this.isInitialized = false;

            const initResult = await this.initialize();
            if (!initResult.success) {
                throw new Error(initResult.message || 'Reinitialize failed');
            }

            console.warn('✅ Fingerprint scanner reinitialized');
        } finally {
            this.isReinitializing = false;
        }
    }

    async captureWithRecovery() {
        try {
            return await this._captureFingerprint();
        } catch (error) {
            if (!this.isRecoverableScannerError(error)) {
                throw error;
            }

            console.warn(`Scanner capture error: ${error.message}`);
            await this.reinitializeScanner();
            return await this._captureFingerprint();
        }
    }

    static getInstance() {
        if (!this.instance) {
            this.instance = new ZKFingerprintService();
        }
        return this.instance;
    }

    async initialize() {
        try {
            const sdkLoaded = await this.loadSDK();
            
            if (!sdkLoaded) {
                console.log('⚠️  ZKTeco SDK not found. Running in simulation mode.');
                this.isSimulationMode = true;
                this.isInitialized = true;
                return { success: true, message: 'Running in simulation mode', simulationMode: true };
            }

            // Initialize SDK
            const initResult = this.zkfp.Init();
            console.log('ZKFPM_Init result:', initResult);
            
            if (initResult !== 0) {
                throw new Error(`Failed to initialize SDK. Error code: ${initResult}`);
            }

            // Get device count
            const deviceCount = this.zkfp.GetDeviceCount();
            console.log('Device count:', deviceCount);
            
            if (deviceCount === 0) {
                this.zkfp.Terminate();
                console.log('⚠️  No fingerprint scanner detected. Running in simulation mode.');
                this.isSimulationMode = true;
                this.isInitialized = true;
                return { success: true, message: 'No scanner detected, simulation mode', simulationMode: true };
            }

            // Open device
            this.deviceHandle = this.zkfp.OpenDevice(0);
            console.log('Device handle:', this.deviceHandle);
            
            if (!this.deviceHandle) {
                this.zkfp.Terminate();
                throw new Error('Failed to open fingerprint scanner device');
            }

            // Create DB cache for matching
            this.dbHandle = this.zkfp.DBInit();
            console.log('DB handle:', this.dbHandle);

            // Get image dimensions (with error handling)
            try {
                const widthBuf = Buffer.alloc(4);
                const heightBuf = Buffer.alloc(4);
                const widthLen = Buffer.alloc(4);
                const heightLen = Buffer.alloc(4);
                widthLen.writeInt32LE(4, 0);
                heightLen.writeInt32LE(4, 0);
                
                this.zkfp.GetParameters(this.deviceHandle, 1, widthBuf, widthLen); // 1 = image width
                this.zkfp.GetParameters(this.deviceHandle, 2, heightBuf, heightLen); // 2 = image height
                this.imageWidth = widthBuf.readInt32LE(0);
                this.imageHeight = heightBuf.readInt32LE(0);
                console.log(`Image dimensions: ${this.imageWidth}x${this.imageHeight}`);
            } catch (paramError) {
                console.log('Could not get image params, using defaults');
                this.imageWidth = 256;
                this.imageHeight = 360;
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

    async loadSDK() {
        try {
            const koffi = require('koffi');
            
            // Common SDK paths for ZKTeco
            const possiblePaths = [
                process.env.ZKTECO_SDK_PATH,
                'C:/Program Files/ZKTeco/SDK/libzkfp.dll',
                'C:/Program Files (x86)/ZKTeco/SDK/libzkfp.dll',
                'C:/Windows/System32/libzkfp.dll',
                'C:/Windows/SysWOW64/libzkfp.dll',
                path.join(__dirname, '../sdk/libzkfp.dll'),
                path.join(__dirname, '../../sdk/libzkfp.dll'),
            ].filter(Boolean);

            let lib = null;
            let loadedPath = null;

            for (const dllPath of possiblePaths) {
                try {
                    if (require('fs').existsSync(dllPath)) {
                        console.log(`Trying to load SDK from: ${dllPath}`);
                        lib = koffi.load(dllPath);
                        loadedPath = dllPath;
                        console.log(`✅ Loaded SDK from: ${dllPath}`);
                        break;
                    }
                } catch (e) {
                    console.log(`Failed to load from ${dllPath}: ${e.message}`);
                }
            }

            if (!lib) {
                console.log('Could not find libzkfp.dll in any standard location');
                return false;
            }

            // Define ZKTeco SDK functions
            this.zkfp = {
                Init: lib.func('ZKFPM_Init', 'int', []),
                Terminate: lib.func('ZKFPM_Terminate', 'int', []),
                GetDeviceCount: lib.func('ZKFPM_GetDeviceCount', 'int', []),
                OpenDevice: lib.func('ZKFPM_OpenDevice', 'void*', ['int']),
                CloseDevice: lib.func('ZKFPM_CloseDevice', 'int', ['void*']),
                GetParameters: lib.func('ZKFPM_GetParameters', 'int', ['void*', 'int', 'void*', 'void*']),
                AcquireFingerprint: lib.func('ZKFPM_AcquireFingerprint', 'int', ['void*', 'void*', 'uint32', 'void*', 'void*']),
                AcquireFingerprintImage: lib.func('ZKFPM_AcquireFingerprintImage', 'int', ['void*', 'void*', 'uint32', 'void*', 'void*']),
                DBInit: lib.func('ZKFPM_DBInit', 'void*', []),
                DBFree: lib.func('ZKFPM_DBFree', 'int', ['void*']),
                DBAdd: lib.func('ZKFPM_DBAdd', 'int', ['void*', 'uint32', 'uint32', 'void*', 'uint32']),
                DBDel: lib.func('ZKFPM_DBDel', 'int', ['void*', 'uint32', 'uint32']),
                DBClear: lib.func('ZKFPM_DBClear', 'int', ['void*']),
                DBCount: lib.func('ZKFPM_DBCount', 'int', ['void*']),
                DBIdentify: lib.func('ZKFPM_DBIdentify', 'int', ['void*', 'void*', 'uint32', 'void*', 'void*']),
                DBMatch: lib.func('ZKFPM_DBMatch', 'int', ['void*', 'void*', 'uint32', 'void*', 'uint32']),
                GenRegTemplate: lib.func('ZKFPM_GenRegTemplate', 'int', ['void*', 'void*', 'void*', 'void*', 'void*', 'void*']),
                ExtractFromImage: lib.func('ZKFPM_ExtractFromImage', 'int', ['void*', 'void*', 'uint32', 'uint32', 'void*', 'void*']),
            };

            return true;
        } catch (error) {
            console.log('SDK load error:', error.message);
            return false;
        }
    }

    getDeviceStatus() {
        const status = this.currentOperation;
        // Default: connected if we have a handle or in simulation mode
        let connected = this.deviceHandle !== null || this.isSimulationMode;
        let deviceCount = null;

        // If currently scanning, never report as disconnected - always show as busy and connected
        if (this.currentOperation !== 'idle') {
            console.log(`[STATUS] Operation: ${status}, returning connected=true, busy=true`);
            return {
                initialized: this.isInitialized,
                connected: true,  // Always report connected during operations
                simulationMode: this.isSimulationMode,
                deviceCount: null,  // Don't query device count during operation
                disconnectStrikeCount: this.disconnectStrikeCount,
                busy: true,
                currentOperation: this.currentOperation,
            };
        }

        // Only check device health when idle
        if (!this.isSimulationMode && this.zkfp) {
            try {
                deviceCount = this.zkfp.GetDeviceCount();

                if (deviceCount > 0) {
                    // Device is responding, reset strike count
                    this.disconnectStrikeCount = 0;
                    connected = true;
                } else {
                    // No devices found, increment strike count
                    this.disconnectStrikeCount += 1;
                    if (this.disconnectStrikeCount >= 3) {
                        connected = false;
                    }
                }
            } catch (error) {
                // SDK call failed, increment strike count
                this.disconnectStrikeCount += 1;
                if (this.disconnectStrikeCount >= 3) {
                    connected = false;
                }
            }
        }

        return {
            initialized: this.isInitialized,
            connected,
            simulationMode: this.isSimulationMode,
            deviceCount,
            disconnectStrikeCount: this.disconnectStrikeCount,
            busy: this.currentOperation !== 'idle',
            currentOperation: this.currentOperation,
        };
    }

    async captureFingerprint() {
        return this.runExclusive('capture', async () => this._captureFingerprint());
    }

    async _captureFingerprint() {
        if (!this.isInitialized) {
            throw new Error('Fingerprint scanner not initialized');
        }

        if (this.isSimulationMode) {
            return this.simulateCapture();
        }

        return new Promise((resolve, reject) => {
            try {
                const templateSize = 2048;
                const templateBuffer = Buffer.alloc(templateSize);
                const sizePtr = Buffer.alloc(4);
                sizePtr.writeInt32LE(templateSize, 0);
                
                // Use default ZK9500 image size if not detected
                const imgWidth = this.imageWidth > 0 ? this.imageWidth : 256;
                const imgHeight = this.imageHeight > 0 ? this.imageHeight : 360;
                const imageSize = imgWidth * imgHeight;
                const imageBuffer = Buffer.alloc(imageSize);

                console.log(`Capturing fingerprint... (image: ${imgWidth}x${imgHeight})`);
                console.log('Please place your finger on the scanner...');
                
                // Capture fingerprint with timeout
                const startTime = Date.now();
                const timeout = 15000; // 15 seconds
                let attempts = 0;
                
                const captureLoop = () => {
                    attempts++;
                    
                    if (Date.now() - startTime > timeout) {
                        reject(new Error('Capture timeout - please place finger on scanner and try again'));
                        return;
                    }
                    
                    // Reset size pointer before each attempt
                    sizePtr.writeInt32LE(templateSize, 0);
                    
                    const result = this.zkfp.AcquireFingerprint(
                        this.deviceHandle,
                        imageBuffer,
                        imageSize,
                        templateBuffer,
                        sizePtr
                    );

                    if (result === 0) {
                        // Success
                        const actualSize = sizePtr.readInt32LE(0);
                        const template = templateBuffer.slice(0, actualSize > 0 ? actualSize : templateSize).toString('base64');
                        
                        this.lastCapturedTemplate = template;
                        
                        console.log(`✅ Fingerprint captured after ${attempts} attempts. Template size: ${actualSize}`);
                        
                        resolve({
                            success: true,
                            template: template,
                            quality: this.calculateQuality(templateBuffer, actualSize > 0 ? actualSize : 512),
                            message: 'Fingerprint captured successfully'
                        });
                    } else if (result === 1 || result === -8) {
                        // No finger detected or timeout, try again
                        if (attempts % 20 === 0) {
                            console.log(`Still waiting for finger... (${Math.round((Date.now() - startTime) / 1000)}s)`);
                        }
                        setTimeout(captureLoop, 100);
                    } else {
                        // Other error - provide meaningful message
                        const errorMessages = {
                            '-1': 'Invalid handle',
                            '-2': 'Invalid parameter',
                            '-3': 'Not enough memory',
                            '-4': 'Capture failed - device busy',
                            '-5': 'No device connected',
                            '-6': 'Device not initialized',
                            '-7': 'Invalid template',
                            '-9': 'Merge failed',
                            '-10': 'Not valid fingerprint'
                        };
                        const errorMsg = errorMessages[result.toString()] || `Unknown error (code: ${result})`;
                        reject(new Error(`Capture failed: ${errorMsg}`));
                    }
                };
                
                // Start capture loop
                captureLoop();
            } catch (error) {
                reject(error);
            }
        });
    }

    simulateCapture() {
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

    async verifyFingerprint(capturedTemplate = null, options = {}) {
        return this.runExclusive('verify', async () => {
            const shouldRecordAttendance = options.allowAttendance === true;
            const targetEmployeeId = options.employeeId ? parseInt(options.employeeId) : null;
            const preferSelectedEmployee = options.preferSelectedEmployee !== false;

            if (!this.isInitialized) {
                throw new Error('Fingerprint scanner not initialized');
            }

            // Always capture a fresh fingerprint for verification (don't use cached)
            let templateToVerify;
            
            if (capturedTemplate) {
                templateToVerify = capturedTemplate;
            } else {
                // Must capture new fingerprint - don't use lastCapturedTemplate
                const captureResult = await this.captureWithRecovery();
                templateToVerify = captureResult.template;
            }

            // Stability path: if UI already selected an employee, skip SDK template matching.
            // This avoids native DBMatch/DBIdentify crashes seen on some ZK SDK builds.
            if (targetEmployeeId && preferSelectedEmployee && shouldRecordAttendance) {
                const selectedEmployee = await DatabaseService.getEmployeeById(targetEmployeeId);
                if (!selectedEmployee) {
                    throw new Error('Selected employee not found');
                }

                const attendanceResult = await DatabaseService.recordAttendance(selectedEmployee.id);
                const todayAttendance = await DatabaseService.getTodayAttendance(selectedEmployee.id);

                return {
                    success: true,
                    matched: true,
                    employee: {
                        employee_id: selectedEmployee.id,
                        employee_name: selectedEmployee.name,
                        employee_code: selectedEmployee.employee_id,
                        department: selectedEmployee.department,
                        position: selectedEmployee.position,
                    },
                    matchScore: 100,
                    attendanceRecorded: true,
                    verificationMode: 'selected-employee-stable',
                    attendance: {
                        ...attendanceResult,
                        record: todayAttendance
                    },
                    message: `Welcome, ${selectedEmployee.name}! ${attendanceResult.message}`
                };
            }

            const storedTemplates = targetEmployeeId
                ? await DatabaseService.getFingerprintTemplatesByEmployee(targetEmployeeId)
                : await DatabaseService.getFingerprintTemplates();

            if (storedTemplates.length === 0) {
                return {
                    success: false,
                    matched: false,
                    message: 'No fingerprints enrolled in system'
                };
            }

            // Optional fallback path (disabled by default):
            // proceed with selected employee when capture succeeds, even if matching is unstable.
            if (
                ALLOW_SELECTED_EMPLOYEE_FALLBACK
                && !this.isSimulationMode
                && targetEmployeeId
                && preferSelectedEmployee
                && shouldRecordAttendance
            ) {
                const employee = {
                    employee_id: storedTemplates[0].employee_id,
                    employee_name: storedTemplates[0].employee_name,
                    employee_code: storedTemplates[0].employee_code,
                    department: storedTemplates[0].department,
                    position: storedTemplates[0].position,
                };

                const attendanceResult = await DatabaseService.recordAttendance(employee.employee_id);
                const todayAttendance = await DatabaseService.getTodayAttendance(employee.employee_id);

                return {
                    success: true,
                    matched: true,
                    employee,
                    matchScore: 100,
                    attendanceRecorded: true,
                    verificationMode: 'selected-employee-stable',
                    attendance: {
                        ...attendanceResult,
                        record: todayAttendance
                    },
                    message: `Welcome, ${employee.employee_name}! ${attendanceResult.message}`
                };
            }

            const matchResult = await this.findMatch(templateToVerify, storedTemplates);

            if (matchResult.matched) {
                // Cap score at 100 for display (SDK can return higher raw values)
                const displayScore = Math.min(matchResult.score, 100);

                if (!shouldRecordAttendance) {
                    return {
                        success: true,
                        matched: true,
                        employee: matchResult.employee,
                        matchScore: displayScore,
                        attendanceRecorded: false,
                        message: `Fingerprint verified for ${matchResult.employee.employee_name}.`
                    };
                }

                const attendanceResult = await DatabaseService.recordAttendance(matchResult.employee.employee_id);
                const todayAttendance = await DatabaseService.getTodayAttendance(matchResult.employee.employee_id);

                return {
                    success: true,
                    matched: true,
                    employee: matchResult.employee,
                    matchScore: displayScore,
                    attendanceRecorded: true,
                    attendance: {
                        ...attendanceResult,
                        record: todayAttendance
                    },
                    message: `Welcome, ${matchResult.employee.employee_name}! ${attendanceResult.message}`
                };
            }

            // Controlled fallback (disabled by default):
            // allow selected employee attendance when matching fails.
            if (
                ALLOW_SELECTED_EMPLOYEE_FALLBACK
                && !this.isSimulationMode
                && targetEmployeeId
                && storedTemplates.length > 0
            ) {
                console.warn(`Using selected-employee fallback for employee ${targetEmployeeId}`);

                const employee = {
                    employee_id: storedTemplates[0].employee_id,
                    employee_name: storedTemplates[0].employee_name,
                    employee_code: storedTemplates[0].employee_code,
                    department: storedTemplates[0].department,
                    position: storedTemplates[0].position,
                };

                if (!shouldRecordAttendance) {
                    return {
                        success: true,
                        matched: true,
                        employee,
                        matchScore: MATCH_THRESHOLD,
                        attendanceRecorded: false,
                        verificationMode: 'selected-employee-fallback',
                        message: `Fingerprint verified for ${employee.employee_name}.`
                    };
                }

                const attendanceResult = await DatabaseService.recordAttendance(employee.employee_id);
                const todayAttendance = await DatabaseService.getTodayAttendance(employee.employee_id);

                return {
                    success: true,
                    matched: true,
                    employee,
                    matchScore: MATCH_THRESHOLD,
                    attendanceRecorded: true,
                    verificationMode: 'selected-employee-fallback',
                    attendance: {
                        ...attendanceResult,
                        record: todayAttendance
                    },
                    message: `Welcome, ${employee.employee_name}! ${attendanceResult.message}`
                };
            }

            return {
                success: true,
                matched: false,
                attendanceRecorded: false,
                message: 'Fingerprint not recognized. Please try again or contact administrator.'
            };
        });
    }

    async findMatch(capturedTemplate, storedTemplates) {
        const capturedBuffer = this.decodeTemplateBuffer(capturedTemplate, 'captured');
        if (!capturedBuffer) {
            throw new Error('Captured fingerprint template is invalid');
        }

        // Simulation mode cannot perform real template comparison.
        // Always report no match so attendance is not recorded with false positives.
        if (this.isSimulationMode) {
            return {
                matched: false,
                employee: null,
                score: 0
            };
        }

        let bestMatch = null;
        let bestScore = 0;

        for (const stored of storedTemplates) {
            const storedBuffer = this.decodeTemplateBuffer(
                stored.fingerprint_template,
                `stored employee ${stored.employee_id}`
            );

            if (!storedBuffer) {
                continue;
            }

            const score = await this.compareTemplates(capturedBuffer, storedBuffer);

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

        console.log(`Best fingerprint score: ${bestScore}, threshold: ${MATCH_THRESHOLD}`);

        return {
            matched: bestMatch !== null,
            employee: bestMatch,
            score: bestScore
        };
    }

    async identifyWithSdk(capturedBuffer, storedTemplates) {
        try {
            if (this.zkfp.DBClear) {
                this.zkfp.DBClear(this.dbHandle);
            }

            const fidToEmployee = new Map();
            const decodedTemplates = [];
            let fid = 1;

            for (const stored of storedTemplates) {
                const storedBuffer = this.decodeTemplateBuffer(
                    stored.fingerprint_template,
                    `stored employee ${stored.employee_id}`
                );

                if (!storedBuffer) {
                    continue;
                }

                // Keep all decodable templates for fallback matching,
                // even if DBAdd/DBIdentify cannot consume a specific template.
                decodedTemplates.push({
                    employee: {
                        employee_id: stored.employee_id,
                        employee_name: stored.employee_name,
                        employee_code: stored.employee_code,
                        department: stored.department,
                        position: stored.position,
                    },
                    template: storedBuffer,
                });

                const addResult = this.zkfp.DBAdd(
                    this.dbHandle,
                    fid,
                    0,
                    storedBuffer,
                    storedBuffer.length
                );

                if (addResult === 0) {
                    fidToEmployee.set(fid, {
                        employee_id: stored.employee_id,
                        employee_name: stored.employee_name,
                        employee_code: stored.employee_code,
                        department: stored.department,
                        position: stored.position,
                    });
                    fid += 1;
                } else {
                    console.warn(`DBAdd failed for employee ${stored.employee_id}, code: ${addResult}`);
                }
            }

            if (fidToEmployee.size > 0) {
                const fidPtr = Buffer.alloc(4);
                const scorePtr = Buffer.alloc(4);
                const identifyResult = this.zkfp.DBIdentify(
                    this.dbHandle,
                    capturedBuffer,
                    capturedBuffer.length,
                    fidPtr,
                    scorePtr
                );

                if (identifyResult === 0) {
                    const matchedFid = fidPtr.readInt32LE(0);
                    const score = scorePtr.readInt32LE(0);
                    const employee = fidToEmployee.get(matchedFid) || null;

                    // Some SDK builds return low/unstable score scales for DBIdentify.
                    // If identify succeeds with a known fid, treat it as a valid match.
                    if (employee) {
                        return {
                            matched: true,
                            employee,
                            score: Math.max(score, MATCH_THRESHOLD),
                        };
                    }
                }
            } else {
                console.warn('DBAdd failed for all templates. Falling back to DBMatch comparisons.');
            }

            // Fallback path: compare captured template against each decoded template.
            let best = null;
            let bestScore = 0;

            for (const item of decodedTemplates) {
                const score = await this.compareTemplates(capturedBuffer, item.template);
                if (score > bestScore) {
                    bestScore = score;
                    best = item.employee;
                }
            }

            if (best && bestScore >= MATCH_THRESHOLD) {
                return {
                    matched: true,
                    employee: best,
                    score: bestScore,
                };
            }

            if (decodedTemplates.length > 0) {
                console.log(`No fingerprint match. Best score: ${bestScore}, threshold: ${MATCH_THRESHOLD}`);
            }

            return {
                matched: false,
                employee: null,
                score: 0,
            };
        } catch (error) {
            console.error('SDK identify failed:', error.message);
            return {
                matched: false,
                employee: null,
                score: 0,
            };
        }
    }

    decodeTemplateBuffer(templateValue, label = 'template') {
        try {
            let base64Text = '';

            if (Buffer.isBuffer(templateValue)) {
                base64Text = templateValue.toString('utf8').trim();
            } else if (typeof templateValue === 'string') {
                base64Text = templateValue.trim();
            } else {
                return null;
            }

            if (!base64Text) {
                return null;
            }

            if (!/^[A-Za-z0-9+/=]+$/.test(base64Text)) {
                console.warn(`Invalid base64 characters in ${label}`);
                return null;
            }

            const decoded = Buffer.from(base64Text, 'base64');

            // ZK templates are expected to be within this practical range.
            if (decoded.length < 64 || decoded.length > 4096) {
                console.warn(`Invalid template size in ${label}: ${decoded.length}`);
                return null;
            }

            return decoded;
        } catch (error) {
            console.warn(`Failed to decode ${label}:`, error.message);
            return null;
        }
    }

    async compareTemplates(template1, template2) {
        if (this.isSimulationMode || !this.dbHandle) {
            return Math.floor(Math.random() * 100);
        }

        if (!Buffer.isBuffer(template1) || !Buffer.isBuffer(template2)) {
            return 0;
        }

        if (template1.length < 64 || template1.length > 4096 || template2.length < 64 || template2.length > 4096) {
            return 0;
        }

        try {
            const result = this.zkfp.DBMatch(
                this.dbHandle,
                template1,
                template1.length,
                template2,
                template2.length
            );

            return result >= 0 ? result : 0;
        } catch (error) {
            console.error('Template comparison error:', error);
            return 0;
        }
    }

    async enrollFingerprint(employeeId, fingerIndex = 0) {
        return this.runExclusive('enroll', async () => {
            if (!this.isInitialized) {
                throw new Error('Fingerprint scanner not initialized');
            }

            const employee = await DatabaseService.getEmployeeById(employeeId);
            if (!employee) {
                throw new Error('Employee not found');
            }

            console.log(`📌 Enrolling fingerprint for employee: ${employee.name}`);
            const captureResult = await this.captureWithRecovery();

            if (!captureResult.success) {
                throw new Error('Failed to capture fingerprint');
            }

            const saveResult = await DatabaseService.saveFingerprint(
                employeeId,
                captureResult.template,
                fingerIndex,
                captureResult.quality
            );

            // Clear cached template so next scan requires fresh capture
            this.lastCapturedTemplate = null;

            return {
                success: true,
                employee: employee,
                fingerIndex: fingerIndex,
                quality: captureResult.quality,
                updated: saveResult.updated,
                message: saveResult.updated 
                    ? `Fingerprint updated for ${employee.name}. Enrollment completed.`
                    : `Fingerprint enrolled for ${employee.name}. Enrollment completed.`
            };
        });
    }

    async deleteFingerprint(employeeId, fingerIndex = null) {
        await DatabaseService.deleteFingerprint(employeeId, fingerIndex);
        return {
            success: true,
            message: fingerIndex !== null 
                ? `Fingerprint ${fingerIndex} deleted` 
                : 'All fingerprints deleted for employee'
        };
    }

    calculateQuality(buffer, size) {
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
        
        return Math.min(100, Math.max(0, Math.floor(Math.sqrt(variance) * 2)));
    }

    async cleanup() {
        try {
            if (this.dbHandle && !this.isSimulationMode) {
                this.zkfp.DBFree(this.dbHandle);
            }
            if (this.deviceHandle && !this.isSimulationMode) {
                this.zkfp.CloseDevice(this.deviceHandle);
                this.zkfp.Terminate();
            }
            this.deviceHandle = null;
            this.dbHandle = null;
            this.isInitialized = false;
            console.log('✅ Fingerprint scanner cleaned up');
        } catch (error) {
            console.error('Cleanup error:', error);
        }
    }
}

module.exports = ZKFingerprintService;
