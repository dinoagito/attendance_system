@extends('layouts.app')

@section('title', 'Enroll Fingerprint')

@section('content')
<div class="page-header">
    <h1>Enroll Fingerprint (ZK9500)</h1>
    <p>Use this page to enroll an employee's fingerprint. This page is separate from the kiosk scanner.</p>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-fingerprint"></i> ZK9500 Scanner</span>
                <span id="scanner-status" class="badge bg-warning"><i class="fas fa-spinner fa-spin"></i> Connecting...</span>
            </div>
            <div class="card-body text-center" style="padding: 40px 20px;">
                <div id="scanner-visual" class="scanner-container mb-4">
                    <div class="scanner-icon"><i class="fas fa-fingerprint"></i></div>
                    <div class="scanner-ring"></div>
                </div>

                <div id="scan-status" class="status-box status-waiting mb-4">
                    <h5 id="status-title">Connecting to Scanner...</h5>
                    <p id="status-message" class="mb-0">
                        <i class="fas fa-circle-notch fa-spin"></i>
                        Please wait while connecting to ZK9500...
                    </p>
                </div>

                <div id="result-waiting" class="d-none">
                    <div class="alert alert-info">
                        <strong><i class="fas fa-info-circle"></i> Ready to Enroll</strong><br>
                        <p class="mb-0">Select an employee and click "Start Enrollment"</p>
                    </div>
                </div>

                <div id="result-success" class="d-none text-center">
                    <div class="verified-banner mb-4">
                        <div class="verified-badge">
                            <i class="fas fa-check-circle"></i>
                            <span>Enrollment Successful</span>
                        </div>
                        <p class="mb-0 mt-2">Fingerprint has been successfully enrolled and saved.</p>
                    </div>
                </div>

                <div id="result-error" class="d-none text-center">
                    <div class="alert alert-danger">
                        <h5 class="mb-1"><i class="fas fa-exclamation-triangle"></i> Enrollment Failed</h5>
                        <p class="mb-0" id="error-message">An error occurred</p>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button id="btn-enroll" class="btn btn-success" disabled style="padding:12px 18px; font-size:1.05rem; font-weight:600; border-radius:8px;">
                        <i class="fas fa-fingerprint"></i> Start Enrollment
                    </button>
                    <button id="btn-reset" class="btn btn-outline-secondary" style="padding:9px 18px; border-radius:8px;">
                        <i class="fas fa-redo-alt"></i> Reset
                    </button>
                </div>
                <!-- Back button below fingerprint scanning section - returns to Employee Management, preserves context -->
                <a href="{{ $validatedReturnUrl ?? route('users.index') }}" class="btn btn-secondary w-100 mt-3" style="padding:10px 18px; border-radius:8px; font-weight:500;">
                    <i class="fas fa-arrow-left"></i> Back to Employee Management
                </a>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <small class="text-muted">
                    <i class="fas fa-usb"></i> Scanner: <span id="device-info">ZK9500</span>
                    <br>
                    <i class="fas fa-clock"></i> Current Time: <span id="current-time">--:--:--</span>
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-user-plus"></i> Employee Information</div>
            <div class="card-body">
                <div class="mb-4">
                    <label class="form-label fw-semibold">Select Employee</label>
                    <select id="enroll-employee" class="form-select form-select-lg">
                        <option value="">-- Select Employee --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->employee_id_number }})</option>
                        @endforeach
                    </select>
                    <small class="text-muted d-block mt-2">Choose the employee whose fingerprint will be enrolled.</small>
                </div>

                <div id="employee-info-display" class="d-none">
                    <div class="alert alert-info">
                        <p class="mb-1"><strong id="selected-emp-name">--</strong></p>
                        <p class="mb-0 text-muted"><small>ID: <span id="selected-emp-id">--</span></small></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="fas fa-book"></i> Enrollment Instructions</div>
            <div class="card-body" style="font-size: 0.95rem;">
                <ol class="mb-0">
                    <li class="mb-2">
                        <strong>Select Employee:</strong> Choose the employee from the dropdown on the left.
                    </li>
                    <li class="mb-2">
                        <strong>Click Start Enrollment:</strong> Click the green "Start Enrollment" button to begin.
                    </li>
                    <li class="mb-2">
                        <strong>Place Finger:</strong> When prompted, place your finger on the scanner.
                    </li>
                    <li class="mb-2">
                        <strong>Multiple Scans:</strong> The system may require multiple finger scans for better accuracy.
                    </li>
                    <li class="mb-2">
                        <strong>Success:</strong> A success message will appear when enrollment is complete.
                    </li>
                    <li>
                        <strong>Reset:</strong> Click "Reset" to clear and start over if needed.
                    </li>
                </ol>

                <div class="alert alert-warning mt-4 mb-0">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Important:</strong>
                    <p class="mb-0 mt-2">Make sure the scanner is properly connected and the employee is ready before starting the enrollment process.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notification Modal -->
<div class="modal fade" id="enrollNotificationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="enrollNotificationTitle">Notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div id="enrollNotificationIcon" class="mb-3" style="font-size: 52px; color: #0d6efd;">
                    <i class="fas fa-circle-info"></i>
                </div>
                <div id="enrollNotificationMessage" class="fs-5">--</div>
            </div>
            <div class="modal-footer">
                <button type="button" id="notification-ok-btn" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Re-enroll Confirmation Modal - Custom replacement for native confirm() -->
<div class="modal fade" id="reenrollConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="border-bottom: 1px solid #e0e0e0;">
                <h5 class="modal-title" style="color: #856404;"><i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i> Already Enrolled</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-3" style="font-size: 52px; color: #ffc107;">
                    <i class="fas fa-fingerprint"></i>
                </div>
                <p class="fs-5 mb-2" style="font-weight: 600; color: #212529;">This employee already has an enrolled fingerprint.</p>
                <p class="text-muted mb-0" id="reenrollConfirmMessage">Re-enrolling will overwrite the existing fingerprint. Do you want to continue?</p>
                <p class="text-muted small mt-2 mb-0" id="reenrollConfirmEmployee" style="font-weight: 500;"></p>
            </div>
            <div class="modal-footer justify-content-center gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="reenrollCancelBtn">Cancel</button>
                <button type="button" class="btn btn-warning" id="reenrollContinueBtn" style="background-color: #ffc107; border-color: #ffc107; color: #212529; font-weight: 600;"><i class="fas fa-redo-alt"></i> Continue Re-enrollment</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const FINGERPRINT_SERVER_PRIMARY = 'http://127.0.0.1:3001';
    const FINGERPRINT_SERVER_FALLBACK = 'http://localhost:3001';
    const pageParams = new URLSearchParams(window.location.search);
    const preselectEmployeeId = pageParams.get('employee_id');
    const employeeDirectory = @json($employeeDirectory ?? []);
    const enrolledIds = @json($enrolledIds ?? []);

    let activeFingerprintServer = FINGERPRINT_SERVER_PRIMARY;
    let isEnrolling = false;

    function $(id){return document.getElementById(id)}
    const scannerVisual = $('scanner-visual');
    const scanStatus = $('scan-status');
    const statusTitle = $('status-title');
    const statusMessage = $('status-message');
    const scannerStatus = $('scanner-status');
    const btnEnroll = $('btn-enroll');
    const btnReset = $('btn-reset');
    const enrollEmployee = $('enroll-employee');
    const resultWaiting = $('result-waiting');
    const resultSuccess = $('result-success');
    const resultError = $('result-error');
    const employeeInfoDisplay = $('employee-info-display');
    const selectedEmpName = $('selected-emp-name');
    const selectedEmpId = $('selected-emp-id');

    const notificationModalEl = document.getElementById('enrollNotificationModal');
    const notificationModal = notificationModalEl ? new bootstrap.Modal(notificationModalEl) : null;
    const notificationTitle = document.getElementById('enrollNotificationTitle');
    const notificationMessage = document.getElementById('enrollNotificationMessage');
    const notificationIcon = document.getElementById('enrollNotificationIcon');

    function updateClock(){
        const now = new Date();
        const el = document.getElementById('current-time');
        if(el) el.textContent = now.toLocaleTimeString();
    }

    function updateStatus(type, title, message) {
        if(!scanStatus) return;
        scanStatus.className = `status-box status-${type}`;
        statusTitle.textContent = title;
        const icons = {
            waiting: 'fa-hand-point-up',
            enrolling: 'fa-circle-notch fa-spin',
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle'
        };
        statusMessage.innerHTML = `<i class="fas ${icons[type] || 'fa-info-circle'}"></i> ${message}`;
    }

    function setEnrolling(enrolling) {
        isEnrolling = enrolling;
        btnEnroll.disabled = enrolling;
        btnEnroll.innerHTML = enrolling ? '<i class="fas fa-circle-notch fa-spin"></i> Enrolling...' : '<i class="fas fa-fingerprint"></i> Start Enrollment';
    }

    function showResult(type){
        [resultWaiting, resultSuccess, resultError].forEach(el=> el && el.classList.add('d-none'));
        const panels = { waiting: resultWaiting, success: resultSuccess, error: resultError };
        if(panels[type]) panels[type].classList.remove('d-none');
    }

    function showNotification({ title, message, type = 'info' }){
        if(!notificationModal) return;
        const iconMap = { success: 'fa-circle-check', danger: 'fa-triangle-exclamation', warning: 'fa-triangle-exclamation', info: 'fa-circle-info' };
        const colorMap = { success: '#198754', danger: '#dc3545', warning: '#fd7e14', info: '#0d6efd' };
        notificationTitle.textContent = title || 'Notification';
        notificationMessage.textContent = message || '--';
        notificationIcon.innerHTML = `<i class="fas ${iconMap[type] || iconMap.info}"></i>`;
        notificationIcon.style.color = colorMap[type] || colorMap.info;
        notificationModal.show();
    }

    function findEmployeeByNumber(inputValue){
        const normalized = String(inputValue||'').trim().toLowerCase();
        if(!normalized) return null;
        return employeeDirectory.find(emp => emp.employeeNo.toLowerCase() === normalized) || employeeDirectory.find(emp => emp.employeeNo.toLowerCase().includes(normalized));
    }

    async function fetchFingerprint(path, options = {}){
        const servers = [activeFingerprintServer];
        const alternate = activeFingerprintServer === FINGERPRINT_SERVER_PRIMARY ? FINGERPRINT_SERVER_FALLBACK : FINGERPRINT_SERVER_PRIMARY;
        if(!servers.includes(alternate)) servers.push(alternate);
        let lastError = null;
        for(const server of servers){
            try{ const res = await fetch(`${server}${path}`, options); activeFingerprintServer = server; return res; }catch(e){ lastError = e; }
        }
        throw lastError || new Error('Cannot connect to fingerprint server');
    }

    async function pollServerHealth(){
        try {
            const response = await fetchFingerprint('/health', { cache: 'no-store' });
            if (!response || !response.ok) {
                console.warn('[zk9500_enroll] health response not ok (direct)', response);
                // try proxy
                const proxyRes = await fetch('/fingerprint/health', { cache: 'no-store' });
                if (!proxyRes.ok) throw new Error('proxy health not ok');
                const proxyData = await proxyRes.json();
                console.log('[zk9500_enroll] health via proxy', proxyData);
                // Check simulation first - show warning and disable enrollment
                if (proxyData?.scanner?.simulationMode) {
                    scannerStatus.className = 'badge bg-warning';
                    scannerStatus.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Simulation Mode';
                    if (btnEnroll) btnEnroll.disabled = true;
                    updateStatus('error', 'Simulation Mode', 'No real scanner connected. Enrollment requires ZK9500 device. Please connect scanner.');
                    return;
                }
                if (proxyData?.status === 'ok' && (proxyData?.scanner?.initialized || proxyData?.scanner?.connected)) {
                    scannerStatus.className = 'badge bg-success';
                    scannerStatus.innerHTML = '<i class="fas fa-check-circle"></i> Connected (proxy)';
                    if (btnEnroll) btnEnroll.disabled = false;
                    updateStatus('waiting', 'Ready to Enroll', 'Select an employee and click "Start Enrollment"');
                    return;
                }
                throw new Error('proxy unhealthy');
            }

            const data = await response.json();
            console.log('[zk9500_enroll] health via fetchFingerprint', activeFingerprintServer, data);

            // Check simulation first
            if (data?.scanner?.simulationMode) {
                scannerStatus.className = 'badge bg-warning';
                scannerStatus.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Simulation Mode';
                if (btnEnroll) btnEnroll.disabled = true;
                updateStatus('error', 'Simulation Mode', 'No real scanner connected. Enrollment requires ZK9500 device. Please connect scanner.');
                return;
            }

            if (data?.status === 'ok' && (data?.scanner?.initialized || data?.scanner?.connected)) {
                scannerStatus.className = 'badge bg-success';
                scannerStatus.innerHTML = '<i class="fas fa-check-circle"></i> Connected';
                if (btnEnroll) btnEnroll.disabled = false;
                updateStatus('waiting', 'Ready to Enroll', 'Select an employee and click "Start Enrollment"');
                return;
            }

            // Not healthy
            scannerStatus.className = 'badge bg-danger';
            scannerStatus.innerHTML = '<i class="fas fa-times-circle"></i> Scanner not connected';
            if (btnEnroll) btnEnroll.disabled = true;
            updateStatus('error', 'Not Connected', 'Fingerprint server returned unhealthy status');
        } catch (err) {
            console.error('[zk9500_enroll] pollServerHealth error', err);
            // try proxy as last resort
            try {
                const proxyRes = await fetch('/fingerprint/health', { cache: 'no-store' });
                if (proxyRes.ok) {
                    const proxyData = await proxyRes.json();
                    console.log('[zk9500_enroll] health via proxy fallback', proxyData);
                    if (proxyData?.scanner?.simulationMode) {
                        scannerStatus.className = 'badge bg-warning';
                        scannerStatus.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Simulation Mode';
                        if (btnEnroll) btnEnroll.disabled = true;
                        updateStatus('error', 'Simulation Mode', 'No real scanner connected. Enrollment requires ZK9500 device. Please connect scanner.');
                        return;
                    }
                    if (proxyData?.status === 'ok' && (proxyData?.scanner?.initialized || proxyData?.scanner?.connected)) {
                        scannerStatus.className = 'badge bg-success';
                        scannerStatus.innerHTML = '<i class="fas fa-check-circle"></i> Connected (proxy)';
                        if (btnEnroll) btnEnroll.disabled = false;
                        updateStatus('waiting', 'Ready to Enroll', 'Select an employee and click "Start Enrollment"');
                        return;
                    }
                }
            } catch (e) {
                console.warn('[zk9500_enroll] proxy fallback failed', e && e.name ? e.name : e);
            }

            scannerStatus.className = 'badge bg-danger';
            scannerStatus.innerHTML = '<i class="fas fa-times-circle"></i> Disconnected';
            if (btnEnroll) btnEnroll.disabled = true;
            updateStatus('error', 'Not Connected', err.message || 'Cannot connect to fingerprint server');
        }
    }

    async function startEnrollment(){
        const employeeId = enrollEmployee.value;
        if(!employeeId){ showNotification({ title: 'Select Employee', message: 'Please select an employee first.', type: 'warning' }); return; }
        try{
            setEnrolling(true); showResult('waiting'); scannerVisual && (scannerVisual.className = 'scanner-container scanning'); updateStatus('enrolling','Enrolling...','Place your finger on the scanner');
            const response = await fetchFingerprint('/api/enroll', { method: 'POST', headers: { 'Content-Type':'application/json' }, body: JSON.stringify({ employeeId: parseInt(employeeId) }) });
            const data = await response.json(); handleEnrollResult(data);
        }catch(err){ console.error('Enroll error:', err); handleError(err.message || 'Enroll failed'); }
    }

    function handleEnrollResult(data){ setEnrolling(false); if(data.success){ scannerVisual && (scannerVisual.className='scanner-container success'); updateStatus('success','Enrolled!',data.message); showNotification({ title:'Fingerprint Enrolled', message: data.message || 'Fingerprint enrolled successfully.', type: 'success' }); showResult('success'); } else { scannerVisual && (scannerVisual.className='scanner-container error'); updateStatus('error','Failed', data.message); showNotification({ title:'Enrollment Failed', message: data.message || 'Unable to enroll fingerprint.', type:'danger' }); showResult('error'); } }

    function handleError(message){ setEnrolling(false); scannerVisual && (scannerVisual.className='scanner-container error'); updateStatus('error','Error', message); const errEl = document.getElementById('error-message'); if(errEl) errEl.textContent = message; showNotification({ title:'Error', message: message || 'An error occurred.', type: 'danger' }); showResult('error'); }

    function resetEnroll(){ setEnrolling(false); scannerVisual && (scannerVisual.className='scanner-container'); updateStatus('waiting','Ready to Enroll','Select an employee and click "Start Enrollment"'); showResult('waiting'); }

    document.addEventListener('DOMContentLoaded', function(){ updateClock(); setInterval(updateClock,1000); pollServerHealth(); setInterval(pollServerHealth,3000);
        if(preselectEmployeeId){ const employee = findEmployeeByNumber(preselectEmployeeId) || employeeDirectory.find(emp => String(emp.id) === String(preselectEmployeeId)); if(employee){ enrollEmployee.value = String(employee.id); selectedEmpName.textContent = employee.name; selectedEmpId.textContent = employee.employeeNo; employeeInfoDisplay.classList.remove('d-none'); // Lock to preselected employee from Registration list
            enrollEmployee.disabled = true;
            const isEnrolled = enrolledIds.includes(String(employee.id));
            if(isEnrolled){
                // Show enrolled warning
                const warn = document.createElement('div');
                warn.id = 'enrolled-warning';
                warn.className = 'alert alert-warning mt-2';
                warn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Already enrolled — re-enrolling will overwrite existing fingerprint for <strong>'+employee.name+'</strong> ('+employee.employeeNo+').';
                employeeInfoDisplay.appendChild(warn);
                updateStatus('waiting','Ready to Re-enroll','This employee already has a fingerprint. Click Start Enrollment to overwrite.');
            } else {
                updateStatus('waiting','Ready to Enroll','Employee auto-selected from Registration. Click Start Enrollment.');
            }
            btnEnroll && btnEnroll.focus(); } else {
                updateStatus('error','Employee Not Found','Employee '+preselectEmployeeId+' not found. Please select manually.');
            } }

        const reenrollModalEl = document.getElementById('reenrollConfirmModal');
        const reenrollModal = reenrollModalEl ? new bootstrap.Modal(reenrollModalEl) : null;
        const reenrollContinueBtn = document.getElementById('reenrollContinueBtn');
        const reenrollConfirmEmployee = document.getElementById('reenrollConfirmEmployee');
        if (reenrollContinueBtn) {
            reenrollContinueBtn.addEventListener('click', function() {
                if (reenrollModal) reenrollModal.hide();
                setTimeout(() => startEnrollment(), 300);
            });
        }

        btnEnroll && btnEnroll.addEventListener('click', function(){
            const selectedId = enrollEmployee.value;
            if(selectedId && enrolledIds.includes(String(selectedId))){
                const emp = employeeDirectory.find(e => String(e.id) === String(selectedId));
                if (reenrollConfirmEmployee && emp) {
                    reenrollConfirmEmployee.textContent = `${emp.name} (${emp.employeeNo})`;
                } else if (reenrollConfirmEmployee) {
                    reenrollConfirmEmployee.textContent = '';
                }
                if (reenrollModal) {
                    reenrollModal.show();
                } else {
                    startEnrollment();
                }
                return;
            }
            startEnrollment();
        });
        btnReset && btnReset.addEventListener('click', function(){
            // Unlock if was locked via URL preselect
            if(preselectEmployeeId){ enrollEmployee.disabled = false; }
            const w = document.getElementById('enrolled-warning');
            if(w) w.remove();
            resetEnroll();
        });

        enrollEmployee && enrollEmployee.addEventListener('change', function(){ const selectedEmployeeId = parseInt(this.value); const selectedEmployee = employeeDirectory.find(emp => String(emp.id) === String(selectedEmployeeId)); if(!selectedEmployee){ employeeInfoDisplay.classList.add('d-none'); return; } selectedEmpName.textContent = selectedEmployee.name; selectedEmpId.textContent = selectedEmployee.employeeNo; employeeInfoDisplay.classList.remove('d-none'); const isEnrolled = enrolledIds.includes(String(selectedEmployee.id)); const existingWarn = document.getElementById('enrolled-warning'); if(existingWarn) existingWarn.remove(); if(isEnrolled){ const warn = document.createElement('div'); warn.id = 'enrolled-warning'; warn.className = 'alert alert-warning mt-2'; warn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Already enrolled — re-enrolling will overwrite.'; employeeInfoDisplay.appendChild(warn); } });
    });
</script>
@endsection
