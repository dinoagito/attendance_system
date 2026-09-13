@extends('layouts.app')

@section('title', 'Windows Hello Biometric Scan')

@section('content')
<div class="page-header">
    <h1><i class="fas fa-fingerprint"></i> Biometric Employee Scan</h1>
    <p>Use Windows Hello (fingerprint/face) to record employee attendance</p>
</div>

<div class="row">
    <!-- Scan Interface -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-fingerprint"></i> Windows Hello Scanner</span>
                <span id="scanner-status" class="badge bg-success">
                    <i class="fas fa-check-circle"></i> Ready
                </span>
            </div>
            <div class="card-body text-center" style="padding: 40px 20px;">
                <!-- Scanner Visual -->
                <div id="scanner-visual" class="scanner-container mb-4">
                    <div class="scanner-icon">
                        <i class="fas fa-fingerprint"></i>
                    </div>
                    <div class="scanner-ring"></div>
                </div>

                <!-- Status Message -->
                <div id="scan-status" class="status-box status-waiting mb-4">
                    <h5 id="status-title">Ready to Scan</h5>
                    <p id="status-message" class="mb-0">
                        <i class="fas fa-hand-point-up"></i>
                        Click "Start Scanning" to verify attendance
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="d-grid gap-2">
                    <button id="btn-scan" class="btn btn-primary btn-lg">
                        <i class="fas fa-fingerprint"></i> Start Scanning
                    </button>
                    <button id="btn-reset" class="btn btn-outline-secondary">
                        <i class="fas fa-redo-alt"></i> Reset
                    </button>
                </div>
                
                <div class="alert alert-info mt-3 text-start" style="font-size: 0.85rem;">
                    <strong><i class="fas fa-info-circle"></i> How to use:</strong><br>
                    1. <strong>First time?</strong> Select employee below and click "Enroll Biometric"<br>
                    2. <strong>Already enrolled?</strong> Click "Start Scanning" above
                </div>
            </div>
        </div>

        <!-- Connection Info -->
        <div class="card mt-3">
            <div class="card-body">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Using Windows Hello (built-in biometric)
                    <br>
                    <i class="fas fa-clock"></i>
                    Current Time: <span id="current-time">--:--:--</span>
                </small>
            </div>
        </div>

        <!-- Enrollment moved to admin-only: Registration Management → Employee → Fingerprint → Enroll Fingerprint ( /scan/employee/zk9500/enroll ) -->
        <!-- Verification-only kiosk: no enrollment here -->
    </div>

    <!-- Scan Result -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user-check"></i> Scan Result
            </div>
            <div class="card-body" id="result-container">
                <!-- Default State -->
                <div id="result-waiting" class="text-center py-5">
                    <i class="fas fa-hand-point-left text-muted" style="font-size: 60px;"></i>
                    <h5 class="mt-3 text-muted">Waiting for biometric scan</h5>
                    <p class="text-muted">Click "Start Scanning" to record attendance</p>
                </div>

                <!-- Match Found -->
                <div id="result-matched" class="d-none">
                    <div class="alert alert-success text-center mb-4">
                        <h5 class="mb-1">
                            <i class="fas fa-check-circle"></i> Identity Verified
                        </h5>
                        <p class="mb-0" id="match-score">Biometric matched successfully</p>
                    </div>

                    <div class="text-center mb-4">
                        <div class="employee-avatar mb-3">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h4 id="employee-name" class="mb-1">--</h4>
                        <p id="employee-id" class="text-muted mb-0">--</p>
                    </div>

                    <div class="employee-details bg-light p-3 rounded mb-4">
                        <div class="detail-row">
                            <span class="detail-label">Department:</span>
                            <strong id="employee-department">--</strong>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Position:</span>
                            <strong id="employee-position">--</strong>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Action:</span>
                            <strong id="attendance-action" class="text-success">--</strong>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Time:</span>
                            <strong id="attendance-time">--</strong>
                        </div>
                    </div>

                    <div class="alert alert-info text-center" id="attendance-message">
                        <i class="fas fa-info-circle"></i> <span>--</span>
                    </div>
                </div>

                <!-- No Match -->
                <div id="result-nomatch" class="d-none text-center py-4">
                    <div class="alert alert-danger">
                        <h5 class="mb-1">
                            <i class="fas fa-times-circle"></i> Not Recognized
                        </h5>
                        <p class="mb-0">Biometric not enrolled</p>
                    </div>
                    <i class="fas fa-user-slash text-danger" style="font-size: 60px;"></i>
                    <h5 class="mt-3">Biometric Not Registered</h5>
                    <p class="text-muted">Please select your name and enroll your biometric first.</p>
                </div>

                <!-- Error State -->
                <div id="result-error" class="d-none text-center py-4">
                    <div class="alert alert-warning">
                        <h5 class="mb-1">
                            <i class="fas fa-exclamation-triangle"></i> Error
                        </h5>
                        <p class="mb-0" id="error-message">An error occurred</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notification Modal -->
<div class="modal fade" id="webauthnNotificationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="webauthnNotificationTitle">Notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div id="webauthnNotificationIcon" class="mb-3" style="font-size: 52px; color: #0d6efd;">
                    <i class="fas fa-circle-info"></i>
                </div>
                <div id="webauthnNotificationMessage" class="fs-5">--</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Today's Attendance -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-history"></i> Today's Attendance</span>
                <button id="btn-refresh" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>ID</th>
                                <th>Department</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="attendance-tbody">
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .scanner-container {
        position: relative;
        width: 150px;
        height: 150px;
        margin: 0 auto;
    }

    .scanner-icon {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 80px;
        color: #0078D4;
        z-index: 2;
        transition: all 0.3s ease;
    }

    .scanner-ring {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: 4px solid #e0e0e0;
        border-radius: 50%;
        transition: all 0.3s ease;
    }

    .scanner-container.scanning .scanner-ring {
        border-color: #0078D4;
        animation: pulse-ring 1.5s infinite;
    }

    .scanner-container.scanning .scanner-icon {
        animation: pulse-icon 1.5s infinite;
    }

    .scanner-container.success .scanner-icon {
        color: #28a745;
    }

    .scanner-container.success .scanner-ring {
        border-color: #28a745;
    }

    .scanner-container.error .scanner-icon {
        color: #dc3545;
    }

    .scanner-container.error .scanner-ring {
        border-color: #dc3545;
    }

    @keyframes pulse-ring {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.1); opacity: 0.7; }
        100% { transform: scale(1); opacity: 1; }
    }

    @keyframes pulse-icon {
        0%, 100% { transform: translate(-50%, -50%) scale(1); }
        50% { transform: translate(-50%, -50%) scale(1.1); }
    }

    .status-box {
        padding: 20px;
        border-radius: 8px;
        border-left: 5px solid;
    }

    .status-waiting { background-color: #f8f9fa; border-left-color: #6c757d; }
    .status-scanning { background-color: #cce5ff; border-left-color: #0078D4; }
    .status-success { background-color: #d4edda; border-left-color: #28a745; }
    .status-error { background-color: #f8d7da; border-left-color: #dc3545; }

    .employee-avatar {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #0078D4, #005a9e);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        color: white;
        font-size: 36px;
    }

    .employee-details .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #dee2e6;
    }

    .employee-details .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label { color: #6c757d; }
</style>
@endsection

@section('scripts')
<script>
    const webauthnNotificationModalEl = document.getElementById('webauthnNotificationModal');
    const webauthnNotificationModal = webauthnNotificationModalEl ? new bootstrap.Modal(webauthnNotificationModalEl) : null;
    const webauthnNotificationTitle = document.getElementById('webauthnNotificationTitle');
    const webauthnNotificationMessage = document.getElementById('webauthnNotificationMessage');
    const webauthnNotificationIcon = document.getElementById('webauthnNotificationIcon');

    function showWebauthnNotification({ title, message, type = 'info' }) {
        if (!webauthnNotificationModal) {
            return;
        }

        const iconMap = {
            success: 'fa-circle-check',
            danger: 'fa-triangle-exclamation',
            warning: 'fa-triangle-exclamation',
            info: 'fa-circle-info',
        };

        const colorMap = {
            success: '#198754',
            danger: '#dc3545',
            warning: '#fd7e14',
            info: '#0d6efd',
        };

        webauthnNotificationTitle.textContent = title || 'Notification';
        webauthnNotificationMessage.textContent = message || '--';
        webauthnNotificationIcon.innerHTML = `<i class="fas ${iconMap[type] || iconMap.info}"></i>`;
        webauthnNotificationIcon.style.color = colorMap[type] || colorMap.info;
        webauthnNotificationModal.show();
    }

    // Check WebAuthn support and secure context
    document.addEventListener('DOMContentLoaded', function() {
        if (!window.isSecureContext) {
            showWebauthnNotification({
                title: 'Secure Context Required',
                message: 'WebAuthn requires http://localhost:8000/scan/employee (not 127.0.0.1).',
                type: 'warning',
            });
            document.getElementById('btn-scan').disabled = true;
            document.getElementById('btn-enroll').disabled = true;
            return;
        }
        
        if (!window.PublicKeyCredential) {
            showWebauthnNotification({
                title: 'WebAuthn Not Supported',
                message: 'Please use Chrome, Edge, or Firefox.',
                type: 'danger',
            });
            document.getElementById('btn-scan').disabled = true;
            document.getElementById('btn-enroll').disabled = true;
            return;
        }
        
        // Check if platform authenticator is available (Windows Hello)
        PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable()
            .then(available => {
                if (!available) {
                    showWebauthnNotification({
                        title: 'Windows Hello Not Available',
                        message: 'Set up fingerprint in Settings > Accounts > Sign-in options > Fingerprint.',
                        type: 'warning',
                    });
                    document.getElementById('scanner-status').innerHTML = '<i class="fas fa-times-circle"></i> No Windows Hello';
                    document.getElementById('scanner-status').className = 'badge bg-danger';
                }
            });
    });

    const scannerVisual = document.getElementById('scanner-visual');
    const scanStatus = document.getElementById('scan-status');
    const statusTitle = document.getElementById('status-title');
    const statusMessage = document.getElementById('status-message');
    const btnScan = document.getElementById('btn-scan');
    const btnReset = document.getElementById('btn-reset');
    const btnEnroll = document.getElementById('btn-enroll');
    const btnRefresh = document.getElementById('btn-refresh');
    const enrollEmployee = document.getElementById('enroll-employee');

    const resultWaiting = document.getElementById('result-waiting');
    const resultMatched = document.getElementById('result-matched');
    const resultNoMatch = document.getElementById('result-nomatch');
    const resultError = document.getElementById('result-error');

    // Base64URL helpers
    function base64UrlToBuffer(base64url) {
        const padding = '='.repeat((4 - base64url.length % 4) % 4);
        const base64 = base64url.replace(/-/g, '+').replace(/_/g, '/') + padding;
        const binary = atob(base64);
        const buffer = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) {
            buffer[i] = binary.charCodeAt(i);
        }
        return buffer.buffer;
    }

    function bufferToBase64Url(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.length; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
    }

    // Enroll biometric for an employee
    async function enrollBiometric() {
        const employeeId = enrollEmployee.value;
        if (!employeeId) {
            showWebauthnNotification({
                title: 'Select Employee',
                message: 'Please select an employee first.',
                type: 'warning',
            });
            return;
        }

        try {
            setScanning(true);
            updateStatus('scanning', 'Enrolling...', 'Complete Windows Hello verification');
            
            console.log('Starting enrollment for employee:', employeeId);

            // Get registration options from server
            const optionsResponse = await fetch('/webauthn/register/options', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ employee_id: employeeId })
            });

            console.log('Registration options response:', optionsResponse.status);
            const optionsData = await optionsResponse.json();
            console.log('Registration options:', optionsData);
            
            if (!optionsData.success) {
                throw new Error(optionsData.message || 'Failed to get registration options');
            }

            // Prepare credential creation options
            const publicKeyOptions = {
                challenge: base64UrlToBuffer(optionsData.options.challenge),
                rp: optionsData.options.rp,
                user: {
                    id: base64UrlToBuffer(optionsData.options.user.id),
                    name: optionsData.options.user.name,
                    displayName: optionsData.options.user.displayName
                },
                pubKeyCredParams: optionsData.options.pubKeyCredParams,
                authenticatorSelection: optionsData.options.authenticatorSelection,
                timeout: optionsData.options.timeout,
                attestation: optionsData.options.attestation
            };

            // Create credentials using Windows Hello
            const credential = await navigator.credentials.create({
                publicKey: publicKeyOptions
            });

            // Send credential to server
            const registerResponse = await fetch('/webauthn/register/complete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    credential_id: bufferToBase64Url(credential.rawId),
                    public_key: bufferToBase64Url(credential.response.getPublicKey ? credential.response.getPublicKey() : credential.response.attestationObject),
                    device_name: 'Windows Hello'
                })
            });

            const registerData = await registerResponse.json();
            
            if (registerData.success) {
                scannerVisual.className = 'scanner-container success';
                updateStatus('success', 'Enrolled!', registerData.message);
                playSound('success');
                showWebauthnNotification({
                    title: 'Biometric Enrolled',
                    message: registerData.message || 'Enrollment successful.',
                    type: 'success',
                });
            } else {
                throw new Error(registerData.message);
            }

        } catch (error) {
            console.error('Enrollment error:', error);
            scannerVisual.className = 'scanner-container error';
            
            let errorMsg = error.message || 'Could not enroll biometric';
            if (error.name === 'NotAllowedError') {
                errorMsg = 'User cancelled or Windows Hello is not configured';
            } else if (error.name === 'InvalidStateError') {
                errorMsg = 'This credential is already registered';
            } else if (error.name === 'NotSupportedError') {
                errorMsg = 'Windows Hello not available on this device';
            }
            
            updateStatus('error', 'Enrollment Failed', errorMsg);
            showWebauthnNotification({
                title: 'Enrollment Failed',
                message: errorMsg,
                type: 'danger',
            });
            playSound('error');
        } finally {
            setScanning(false);
        }
    }

    // Verify biometric and record attendance
    async function verifyBiometric() {
        try {
            setScanning(true);
            hideAllResults();
            scannerVisual.className = 'scanner-container scanning';
            updateStatus('scanning', 'Verifying...', 'Complete Windows Hello verification');

            console.log('Fetching authentication options...');
            
            // Get authentication options
            const optionsResponse = await fetch('/webauthn/authenticate/options', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            console.log('Response status:', optionsResponse.status);
            const optionsData = await optionsResponse.json();
            console.log('Options data:', optionsData);
            
            if (!optionsData.success) {
                throw new Error(optionsData.message || 'No biometrics enrolled. Please enroll first.');
            }

            // Prepare credential request options
            const publicKeyOptions = {
                challenge: base64UrlToBuffer(optionsData.options.challenge),
                rpId: optionsData.options.rpId,
                allowCredentials: optionsData.options.allowCredentials.map(cred => ({
                    id: base64UrlToBuffer(cred.id),
                    type: cred.type,
                    transports: cred.transports
                })),
                userVerification: optionsData.options.userVerification,
                timeout: optionsData.options.timeout
            };

            // Get credentials using Windows Hello
            const assertion = await navigator.credentials.get({
                publicKey: publicKeyOptions
            });

            // Verify with server — include selected employee for strict check (if a specific employee is selected, only that employee's fingerprint is accepted)
            const selectedEmployeeId = enrollEmployee && enrollEmployee.value ? parseInt(enrollEmployee.value) : null;
            const verifyPayload = {
                credential_id: bufferToBase64Url(assertion.rawId)
            };
            if (selectedEmployeeId) {
                verifyPayload.employee_id = selectedEmployeeId;
            }
            const verifyResponse = await fetch('/webauthn/authenticate/verify', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(verifyPayload)
            });

            const verifyData = await verifyResponse.json();
            
            // Schedule validation: reject if employee has no schedule today
            if (verifyData.schedule_valid === false || verifyData.attendance?.action === 'no_schedule') {
                scannerVisual.className = 'scanner-container error';
                updateStatus('error', 'No Schedule Today', verifyData.message);
                showWebauthnNotification({
                    title: 'No Schedule Today',
                    message: verifyData.message,
                    type: 'warning',
                });
                document.getElementById('error-message').textContent = verifyData.message;
                showResult('error');
                playSound('error');
                return;
            }

            // Strict verification: fingerprint must belong to selected employee
            if (verifyData.success === false && verifyData.matched === false && verifyData.message && verifyData.message.toLowerCase().includes('does not belong')) {
                scannerVisual.className = 'scanner-container error';
                updateStatus('error', 'Verification Failed', verifyData.message);
                showWebauthnNotification({
                    title: 'Verification Failed',
                    message: verifyData.message,
                    type: 'danger',
                });
                document.getElementById('error-message').textContent = verifyData.message;
                showResult('error');
                playSound('error');
                return;
            }

            if (verifyData.matched) {
                scannerVisual.className = 'scanner-container success';
                updateStatus('success', 'Verified!', `Welcome, ${verifyData.employee.name}!`);
                
                document.getElementById('employee-name').textContent = verifyData.employee.name;
                document.getElementById('employee-id').textContent = verifyData.employee.employee_id;
                document.getElementById('employee-department').textContent = verifyData.employee.department || '-';
                document.getElementById('employee-position').textContent = verifyData.employee.position || '-';
                document.getElementById('attendance-action').textContent = 
                    verifyData.attendance.action === 'time_in' ? 'Time In' : 
                    verifyData.attendance.action === 'time_out' ? 'Time Out' : 'Already Recorded';
                document.getElementById('attendance-time').textContent = new Date().toLocaleTimeString();
                document.getElementById('attendance-message').querySelector('span').textContent = 
                    verifyData.attendance.message || 'Attendance recorded';

                const attendanceTitle = verifyData.attendance.action === 'time_in'
                    ? 'Time In Recorded'
                    : verifyData.attendance.action === 'time_out'
                        ? 'Time Out Recorded'
                        : 'Attendance Recorded';
                showWebauthnNotification({
                    title: attendanceTitle,
                    message: verifyData.attendance.message || 'Attendance recorded.',
                    type: 'success',
                });

                showResult('matched');
                playSound('success');
                
                // Refresh the attendance table after a short delay
                setTimeout(() => {
                    console.log('Refreshing attendance table...');
                    loadTodayAttendance();
                }, 500);
            } else {
                scannerVisual.className = 'scanner-container error';
                updateStatus('error', 'Not Found', verifyData.message);
                showResult('nomatch');
                playSound('error');
            }

        } catch (error) {
            console.error('Verification error:', error);
            scannerVisual.className = 'scanner-container error';
            
            let errorMsg = error.message || 'Verification failed';
            
            if (error.name === 'NotAllowedError') {
                errorMsg = 'Verification was cancelled';
                updateStatus('error', 'Cancelled', errorMsg);
                showWebauthnNotification({
                    title: 'Verification Cancelled',
                    message: errorMsg,
                    type: 'warning',
                });
            } else if (errorMsg.includes('No biometrics enrolled') || errorMsg.includes('enroll')) {
                updateStatus('error', 'Not Enrolled', 'No biometrics enrolled yet. Please enroll an employee first.');
                showWebauthnNotification({
                    title: 'No Biometrics Enrolled',
                    message: 'Select an employee, click Enroll Biometric, then try scanning again.',
                    type: 'warning',
                });
            } else {
                updateStatus('error', 'Error', errorMsg);
                showWebauthnNotification({
                    title: 'Error',
                    message: errorMsg,
                    type: 'danger',
                });
                showResult('nomatch');
            }
            playSound('error');
        } finally {
            setScanning(false);
        }
    }

    function resetScan() {
        setScanning(false);
        scannerVisual.className = 'scanner-container';
        updateStatus('ready', 'Ready to Scan', 'Click "Start Scanning" and use your fingerprint');
        showResult('waiting');
    }

    function updateStatus(type, title, message) {
        scanStatus.className = `status-box status-${type === 'ready' ? 'waiting' : type}`;
        statusTitle.textContent = title;
        const icons = { 
            'ready': 'fa-hand-point-up', 
            'scanning': 'fa-circle-notch fa-spin', 
            'success': 'fa-check-circle', 
            'error': 'fa-exclamation-circle' 
        };
        statusMessage.innerHTML = `<i class="fas ${icons[type] || 'fa-info-circle'}"></i> ${message}`;
    }

    function setScanning(scanning) {
        btnScan.disabled = scanning;
        if (btnEnroll) btnEnroll.disabled = scanning;
        btnScan.innerHTML = scanning 
            ? '<i class="fas fa-circle-notch fa-spin"></i> Scanning...' 
            : '<i class="fas fa-fingerprint"></i> Start Scanning';
    }

    function showResult(type) {
        hideAllResults();
        const panels = { 'waiting': resultWaiting, 'matched': resultMatched, 'nomatch': resultNoMatch, 'error': resultError };
        if (panels[type]) panels[type].classList.remove('d-none');
    }

    function hideAllResults() {
        [resultWaiting, resultMatched, resultNoMatch, resultError].forEach(el => el.classList.add('d-none'));
    }

    async function loadTodayAttendance() {
        try {
            console.log('Loading today attendance...');
            const response = await fetch('/webauthn/attendance/today');
            const data = await response.json();
            console.log('Attendance data:', data);
            const tbody = document.getElementById('attendance-tbody');
            
            if (data.attendance?.length > 0) {
                tbody.innerHTML = data.attendance.map(r => `
                    <tr>
                        <td>${r.employee_name}</td>
                        <td>${r.employee_code}</td>
                        <td>${r.department || '-'}</td>
                        <td>${r.time_in || '-'}</td>
                        <td>${r.time_out || '-'}</td>
                        <td><span class="badge ${r.time_out ? 'bg-secondary' : 'bg-success'}">${r.time_out ? 'Completed' : 'On-site'}</span></td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No attendance records for today</td></tr>';
            }
        } catch (error) {
            console.error('Error loading attendance:', error);
            document.getElementById('attendance-tbody').innerHTML = 
                '<tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-exclamation-triangle text-warning"></i> Unable to load attendance</td></tr>';
        }
    }

    function playSound(type) {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = type === 'success' ? 880 : 220;
            osc.type = type === 'success' ? 'sine' : 'square';
            gain.gain.value = 0.1;
            osc.start();
            osc.stop(ctx.currentTime + 0.2);
        } catch (e) {}
    }

    function updateTime() {
        document.getElementById('current-time').textContent = new Date().toLocaleTimeString();
    }

    // Event listeners - verification-only kiosk (enrollment is admin-only via Registration Management)
    btnScan.addEventListener('click', function() {
        console.log('Scan button clicked!');
        verifyBiometric();
    });
    btnReset.addEventListener('click', resetScan);
    if (btnEnroll) {
        btnEnroll.style.display = 'none';
        // Enrollment removed from kiosk
    }
    if (btnRefresh) btnRefresh.addEventListener('click', loadTodayAttendance);

    document.addEventListener('DOMContentLoaded', function() {
        console.log('Page loaded. Testing WebAuthn availability...');
        console.log('isSecureContext:', window.isSecureContext);
        console.log('PublicKeyCredential available:', !!window.PublicKeyCredential);
        console.log('Current URL:', window.location.href);
        
        loadTodayAttendance();
        updateTime();
        setInterval(updateTime, 1000);
        showResult('waiting');
    });
</script>
@endsection
