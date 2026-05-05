@extends('layouts.app')

@section('title', 'Fingerprint Scanner Prototype - ZK9500')

@section('content')
@php
    $employeeDirectory = $employees->map(function ($emp) {
        return [
            'id' => $emp->id,
            'employeeNo' => $emp->employee_id_number,
            'name' => $emp->name,
        ];
    })->values();

    $selectedEmployeeId = null;
    if (request()->query('employee_id')) {
        $param = request()->query('employee_id');
        $selectedEmployee = $employees->firstWhere('employee_id_number', $param) ?: $employees->firstWhere('id', $param);
        $selectedEmployeeId = $selectedEmployee?->id;
    }

    $attendanceMap = [];
    foreach ($todayAttendance as $record) {
        $attendanceMap[$record->employee_id] = $record;
    }
@endphp

<div id="prototype-root" class="prototype-shell">
    <div id="frame1-screen" class="frame1-screen">
        <div class="frame1-layout">
            <div class="frame1-clock-panel">
                <div class="clock-kicker">Prototype Preview</div>
                <div id="frame1-current-time" class="frame1-current-time">--:--:--</div>
                <div id="frame1-current-date" class="frame1-current-date">--- --, ----</div>
            </div>

            <div class="frame1-panel">
                <h1 class="frame-title">Search Employee No.</h1>
                <p class="frame-subtitle">Enter or confirm the employee number from the URL.</p>

                <div class="mb-3">
                    <label for="employee-no-input" class="form-label fw-semibold">Employee No.</label>
                    <input id="employee-no-input" class="form-control form-control-lg" type="text" placeholder="e.g., EMP-2026-004" list="employee-no-suggestions" autocomplete="off">
                    <datalist id="employee-no-suggestions">
                        @foreach($employees as $emp)
                            <option value="{{ $emp->employee_id_number }}">{{ $emp->name }}</option>
                        @endforeach
                    </datalist>
                    <small id="employee-no-hint" class="text-muted">Press Enter to continue.</small>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button id="btn-continue-employee" type="button" class="btn btn-primary btn-lg flex-fill">Continue</button>
                    <button id="btn-clear-employee-no" type="button" class="btn btn-secondary btn-lg">Clear</button>
                </div>
            </div>
        </div>
    </div>

    <div id="frame2-wrapper" class="d-none">
        <div class="page-header prototype-header">
            <h1><i class="fas fa-fingerprint"></i> Employee Fingerprint Enrollment and Attendance</h1>
            <p>Prototype layout rendered as a working page.</p>
        </div>

        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card scanner-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-fingerprint"></i> ZK9500 Scanner</span>
                        <span id="scanner-status" class="badge bg-warning">
                            <i class="fas fa-spinner fa-spin"></i> Connecting...
                        </span>
                    </div>
                    <div class="card-body text-center scanner-card-body" id="result-container">
                        <!-- BEFORE STATE: Ready to Scan -->
                        <div id="result-waiting" class="prototype-state prototype-state-waiting d-flex align-items-center justify-content-center" style="min-height: 380px;">
                            <div style="width: 100%;">
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

                                <div class="d-grid gap-2">
                                    <button id="btn-scan" class="btn btn-primary btn-lg" disabled>
                                        <i class="fas fa-fingerprint"></i> Start Scanning
                                    </button>
                                    <button id="btn-reset" class="btn btn-outline-secondary">
                                        <i class="fas fa-redo-alt"></i> Reset
                                    </button>
                                </div>

                                <div class="alert alert-info mt-3 text-start prototype-help">
                                    <strong><i class="fas fa-info-circle"></i> How to use:</strong><br>
                                    1. Select employee below and click "Enroll Fingerprint"<br>
                                    2. Or click "Start Scanning" to record attendance
                                </div>
                            </div>
                        </div>

                        <!-- AFTER STATE: Verified -->
                        <div id="result-matched" class="prototype-state d-none" style="min-height: 380px; display: flex; align-items: center; justify-content: center;">
                            <div style="width: 100%;">
                                <div class="verified-banner mb-4">
                                    <div class="verified-badge">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Identity Verified</span>
                                    </div>
                                    <div class="match-score-chip" id="match-score">Identity Match: 100%</div>
                                </div>

                                <div class="verified-hero mb-4">
                                    <div class="verified-hero-icon">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="verified-hero-copy">
                                        <h4 id="employee-name" class="mb-1">--</h4>
                                        <p id="employee-id" class="text-muted mb-0">--</p>
                                    </div>
                                </div>

                                <div class="verified-date-time text-center">
                                    <div id="verified-time" class="verified-time">--:--:--</div>
                                    <div id="verified-date" class="verified-date">--- --, ----</div>
                                </div>

                                <div class="alert alert-info text-center mt-4" id="attendance-message">
                                    <i class="fas fa-info-circle"></i> <span>--</span>
                                </div>
                            </div>
                        </div>

                        <!-- NOT RECOGNIZED STATE -->
                        <div id="result-nomatch" class="d-none text-center py-4">
                            <div class="alert alert-danger">
                                <h5 class="mb-1"><i class="fas fa-times-circle"></i> Not Recognized</h5>
                                <p class="mb-0">Fingerprint not enrolled</p>
                            </div>
                            <i class="fas fa-user-slash text-danger" style="font-size: 60px;"></i>
                            <h5 class="mt-3">Fingerprint Not Registered</h5>
                            <p class="text-muted">Please select your name and enroll your fingerprint first.</p>
                        </div>

                        <!-- ERROR STATE -->
                        <div id="result-error" class="d-none text-center py-4">
                            <div class="alert alert-warning">
                                <h5 class="mb-1"><i class="fas fa-exclamation-triangle"></i> Error</h5>
                                <p class="mb-0" id="error-message">An error occurred</p>
                            </div>
                        </div>
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

                <div class="card mt-3">
                    <div class="card-header"><i class="fas fa-user-plus"></i> Enroll New Fingerprint</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Select Employee</label>
                            <select id="enroll-employee" class="form-select">
                                <option value="">-- Select Employee --</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->employee_id_number }})</option>
                                @endforeach
                            </select>
                        </div>
                        <button id="btn-enroll" class="btn btn-success w-100" disabled>
                            <i class="fas fa-fingerprint"></i> Enroll Fingerprint
                        </button>
                    </div>
                </div>

            </div>

            <div class="col-lg-6 mb-4">
                <div class="card mt-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-calendar-alt"></i> Today's Record</span>
                        <div class="d-flex gap-2 align-items-center">
                            <button id="btn-view-all-today" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-users"></i> View All Today's Records
                            </button>
                            <button id="btn-refresh" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="today-record-container">
                            <div class="text-center text-muted py-4">
                                <p class="mb-2"><i class="fas fa-calendar-check"></i> <strong id="today-date">-- -- ----</strong></p>
                                <p class="mb-0" id="today-record-status">No scans recorded yet. Scan an employee to log an event.</p>
                            </div>
                        </div>
                        <div id="today-record-list" class="d-none">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="mb-1" id="today-record-employee-name">--</h6>
                                    <small class="text-muted" id="today-record-employee-id">--</small>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm" id="today-record-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Employee No.</th>
                                            <th>Name</th>
                                            <th>Time In</th>
                                            <th>Time Out</th>
                                            <th>Time In</th>
                                            <th>Time Out</th>
                                            <th>Time In</th>
                                            <th>Time Out</th>
                                            <th>Time In</th>
                                            <th>Time Out</th>
                                        </tr>
                                    </thead>
                                    <tbody id="today-record-tbody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="prototypeNotificationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="prototypeNotificationTitle">Notification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <div id="prototypeNotificationIcon" class="mb-3" style="font-size: 52px; color: #0d6efd;">
                        <i class="fas fa-circle-info"></i>
                    </div>
                    <div id="prototypeNotificationMessage" class="fs-5">--</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Today's Records Modal -->
    <div class="modal fade" id="todayRecordsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-users"></i> Today's Records</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input id="today-records-search" type="search" class="form-control" placeholder="Search employees by name...">
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Employee No.</th>
                                    <th>Name</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                </tr>
                            </thead>
                            <tbody id="today-records-tbody">
                                <tr><td colspan="11" class="text-center text-muted">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

<style>
    .prototype-shell {
        min-height: calc(100vh - 2rem);
    }

    .frame1-screen {
        min-height: calc(100vh - 2rem);
        background: radial-gradient(circle at top, #f8fff9, #d9f2df 60%, #c8ebd0);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
    }

    .frame1-layout {
        width: min(1120px, 100%);
        display: grid;
        grid-template-columns: 1.05fr minmax(420px, 0.95fr);
        gap: 24px;
        align-items: stretch;
    }

    .frame1-clock-panel {
        border-radius: 16px;
        padding: 36px;
        color: #f8fff9;
        background: linear-gradient(145deg, #153828, #1f5f43 60%, #2d7a59);
        box-shadow: 0 14px 40px rgba(0, 0, 0, 0.18);
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .clock-kicker {
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-size: 0.86rem;
        color: rgba(248, 255, 249, 0.75);
    }

    .frame1-current-time {
        font-size: clamp(3.2rem, 8vw, 6rem);
        font-weight: 700;
        line-height: 1;
        letter-spacing: 0.04em;
        font-variant-numeric: tabular-nums;
    }

    .frame1-current-date {
        margin-top: 14px;
        font-size: clamp(1.1rem, 2.1vw, 1.6rem);
        color: rgba(248, 255, 249, 0.92);
        font-weight: 500;
    }

    .frame1-panel {
        width: 100%;
        background: #ffffff;
        border-radius: 16px;
        padding: 28px 30px;
        box-shadow: 0 14px 40px rgba(0, 0, 0, 0.18);
        align-self: stretch;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .frame-title { font-size: 1.6rem; margin-bottom: 6px; }
    .frame-subtitle { color: #4b5563; margin-bottom: 16px; }

    .prototype-header h1 { margin-bottom: 6px; }
    .prototype-header p { margin-bottom: 0; color: #6b7280; }

    .scanner-card-body { padding: 40px 20px; }
    .prototype-help { font-size: 0.85rem; }

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
        color: #28a745;
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

    .scanner-container.scanning .scanner-ring { border-color: #28a745; animation: pulse-ring 1.5s infinite; }
    .scanner-container.scanning .scanner-icon { animation: pulse-icon 1.5s infinite; }
    .scanner-container.success .scanner-icon { color: #28a745; }
    .scanner-container.success .scanner-ring { border-color: #28a745; }
    .scanner-container.error .scanner-icon { color: #dc3545; }
    .scanner-container.error .scanner-ring { border-color: #dc3545; }

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
    .status-scanning { background-color: #d4edda; border-left-color: #28a745; }
    .status-success { background-color: #d4edda; border-left-color: #28a745; }
    .status-error { background-color: #f8d7da; border-left-color: #dc3545; }

    .prototype-state {
        width: 100%;
    }

    .prototype-state-waiting {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 260px;
    }

    .verified-banner {
        background: #d6eadf;
        border: 1px solid #a6d3b8;
        border-radius: 10px;
        padding: 18px 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
    }

    .verified-hero {
        display: flex;
        align-items: center;
        gap: 18px;
        justify-content: center;
    }

    .verified-hero-icon {
        width: 84px;
        height: 84px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #28a745, #20c997);
        color: #fff;
        font-size: 36px;
        flex: 0 0 auto;
        box-shadow: 0 10px 24px rgba(40, 167, 69, 0.25);
    }

    .verified-hero-copy {
        text-align: center;
    }

    .verified-date-time {
        margin-top: 4px;
        text-align: center;
    }

    .verified-time {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
        line-height: 1.1;
    }

    .verified-date {
        margin-top: 4px;
        color: #6b7280;
        font-size: 0.98rem;
    }

    .verified-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #103b28;
        font-weight: 700;
        font-size: 1.05rem;
    }

    .match-score-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #103b28;
        font-weight: 700;
        font-size: 1rem;
    }

    @media (max-width: 992px) {
        .frame1-layout { grid-template-columns: 1fr; }
        .frame1-clock-panel { padding: 24px; text-align: center; }
        .frame1-panel { padding: 24px; }

        .verified-hero {
            flex-direction: column;
            text-align: center;
        }

        .verified-hero-copy {
            text-align: center;
        }

        .prototype-ready-title {
            font-size: 1.7rem;
        }
    }
</style>
@endsection

@section('scripts')
<script>
    const FINGERPRINT_SERVER_PRIMARY = 'http://127.0.0.1:3001';
    const FINGERPRINT_SERVER_FALLBACK = 'http://localhost:3001';
    const pageParams = new URLSearchParams(window.location.search);
    const preselectEmployeeId = pageParams.get('employee_id');
    const employeeDirectory = @json($employeeDirectory);
    const attendanceMap = @json($attendanceMap);

    let activeFingerprintServer = FINGERPRINT_SERVER_PRIMARY;
    let isScanning = false;

    const scannerVisual = document.getElementById('scanner-visual');
    const scanStatus = document.getElementById('scan-status');
    const statusTitle = document.getElementById('status-title');
    const statusMessage = document.getElementById('status-message');
    const scannerStatus = document.getElementById('scanner-status');
    const btnScan = document.getElementById('btn-scan');
    const btnReset = document.getElementById('btn-reset');
    const btnEnroll = document.getElementById('btn-enroll');
    const btnRefresh = document.getElementById('btn-refresh');
    const btnViewAllToday = document.getElementById('btn-view-all-today');
    const enrollEmployee = document.getElementById('enroll-employee');
    const frame1Screen = document.getElementById('frame1-screen');
    const frame2Wrapper = document.getElementById('frame2-wrapper');
    const employeeNoInput = document.getElementById('employee-no-input');
    const employeeNoHint = document.getElementById('employee-no-hint');
    const btnContinueEmployee = document.getElementById('btn-continue-employee');
    const btnClearEmployeeNo = document.getElementById('btn-clear-employee-no');
    const verifiedTime = document.getElementById('verified-time');
    const verifiedDate = document.getElementById('verified-date');

    // Today's record elements
    const todayRecordContainer = document.getElementById('today-record-container');
    const todayRecordList = document.getElementById('today-record-list');
    const todayRecordEmployeeName = document.getElementById('today-record-employee-name');
    const todayRecordEmployeeId = document.getElementById('today-record-employee-id');
    const todayRecordTbody = document.getElementById('today-record-tbody');
    const todayRecordDate = document.getElementById('today-date');
    const todayRecordStatus = document.getElementById('today-record-status');

    // Elements for the "View All" modal (will be null if not present yet)
    const todayRecordsSearch = null;
    const todayRecordsTbody = null;

    const resultWaiting = document.getElementById('result-waiting');
    const resultMatched = document.getElementById('result-matched');
    const resultNoMatch = document.getElementById('result-nomatch');
    const resultError = document.getElementById('result-error');
    let revertTimer = null;

    // Today's record tracking (in-memory) - up to 4 time-in/time-out pairs
    const STORAGE_KEY = 'todayRecordStore';
    const LEGACY_STORAGE_KEY = 'todayRecordData';
    
    function getTodayDateString() {
        return new Date().toDateString();
    }
    
    function getEmptyTodayRecord(employeeId = null, employeeName = null, employeeCode = null) {
        return {
            date: getTodayDateString(),
            employeeId: employeeId,
            employeeName: employeeName,
            employeeCode: employeeCode,
            time_in_1: null,
            time_out_1: null,
            time_in_2: null,
            time_out_2: null,
            time_in_3: null,
            time_out_3: null,
            time_in_4: null,
            time_out_4: null
        };
    }

    function normalizeTodayRecordStore(data) {
        if (!data || typeof data !== 'object') {
            return { date: getTodayDateString(), records: {} };
        }

        const today = getTodayDateString();

        // Migrate old single-record storage shape into the new per-employee store.
        if (data.employeeId) {
            return {
                date: data.date === today ? data.date : today,
                records: {
                    [String(data.employeeId)]: {
                        ...getEmptyTodayRecord(data.employeeId, data.employeeName, data.employeeCode),
                        ...data,
                        date: data.date === today ? data.date : today
                    }
                }
            };
        }

        if (data.date !== today) {
            return { date: today, records: {} };
        }

        return {
            date: today,
            records: data.records && typeof data.records === 'object' ? data.records : {}
        };
    }

    function loadTodayRecordStoreFromStorage() {
        try {
            const stored = localStorage.getItem(STORAGE_KEY) || localStorage.getItem(LEGACY_STORAGE_KEY);
            if (stored) {
                const data = JSON.parse(stored);
                const normalized = normalizeTodayRecordStore(data);
                if (!localStorage.getItem(STORAGE_KEY)) {
                    saveTodayRecordStoreToStorage(normalized);
                }
                return normalized;
            }
        } catch (e) {
            console.error('Error loading today record from storage:', e);
        }
        return { date: getTodayDateString(), records: {} };
    }
    
    function saveTodayRecordStoreToStorage(data) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
            console.log('Saved today record store to storage');
        } catch (e) {
            console.error('Error saving today record to storage:', e);
        }
    }
    
    let todayRecordStore = loadTodayRecordStoreFromStorage();
    let currentTodayEmployeeId = null;
    let todayRecordData = getEmptyTodayRecord();

    function ensureTodayRecordStoreForToday() {
        if (todayRecordStore.date === getTodayDateString()) {
            return;
        }

        todayRecordStore = { date: getTodayDateString(), records: {} };
        currentTodayEmployeeId = null;
        todayRecordData = getEmptyTodayRecord();
        saveTodayRecordStoreToStorage(todayRecordStore);
    }

    function getEmployeeRecordFromStore(employeeId) {
        ensureTodayRecordStoreForToday();

        if (!employeeId) {
            return null;
        }

        return todayRecordStore.records[String(employeeId)] || null;
    }

    function persistCurrentEmployeeRecord() {
        ensureTodayRecordStoreForToday();

        if (!currentTodayEmployeeId || !todayRecordData.employeeId) {
            return;
        }

        todayRecordStore.date = getTodayDateString();
        todayRecordStore.records[String(currentTodayEmployeeId)] = {
            ...getEmptyTodayRecord(
                todayRecordData.employeeId,
                todayRecordData.employeeName,
                todayRecordData.employeeCode
            ),
            ...todayRecordData,
            date: getTodayDateString()
        };
        saveTodayRecordStoreToStorage(todayRecordStore);
    }

    function setActiveTodayRecord(employeeId, employeeName, employeeCode) {
        ensureTodayRecordStoreForToday();

        const normalizedEmployeeId = employeeId ? String(employeeId) : null;
        currentTodayEmployeeId = normalizedEmployeeId;

        if (!normalizedEmployeeId) {
            todayRecordData = getEmptyTodayRecord();
            return;
        }

        const storedRecord = getEmployeeRecordFromStore(normalizedEmployeeId);
        todayRecordData = storedRecord ? {
            ...getEmptyTodayRecord(normalizedEmployeeId, employeeName, employeeCode),
            ...storedRecord,
            employeeId: storedRecord.employeeId || employeeId,
            employeeName: storedRecord.employeeName || employeeName,
            employeeCode: storedRecord.employeeCode || employeeCode,
            date: getTodayDateString()
        } : getEmptyTodayRecord(employeeId, employeeName, employeeCode);

        if (!todayRecordData.employeeId) {
            todayRecordData.employeeId = employeeId;
        }
        if (!todayRecordData.employeeName) {
            todayRecordData.employeeName = employeeName;
        }
        if (!todayRecordData.employeeCode) {
            todayRecordData.employeeCode = employeeCode;
        }

        updateTodayRecordDisplay();
    }

    // Render and search helpers for the "View All Today's Records" modal
    function renderTodayRecordsList(filter = '') {
        ensureTodayRecordStoreForToday();

        const q = String(filter || '').trim().toLowerCase();

        // Build list from full employee directory so we show all employees (with or without records)
        const rows = employeeDirectory
            .map(emp => {
                const rec = todayRecordStore.records[String(emp.id)] || null;
                return {
                    id: emp.id,
                    name: emp.name,
                    employeeNo: emp.employeeNo || emp.employeeNo || emp.employee_id_number || emp.employeeNo,
                    record: rec
                };
            })
            .filter(item => !q || item.name.toLowerCase().includes(q))
            .sort((a, b) => a.name.localeCompare(b.name, undefined, { sensitivity: 'base' }));

        const tbody = document.getElementById('today-records-tbody');
        if (!tbody) return;

        if (rows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="11" class="text-center text-muted">No employees match your search.</td></tr>`;
            return;
        }

        const todayDateStr = getTodayDateString();

        tbody.innerHTML = rows.map(item => {
            const r = item.record || {};
            // Only display records for today (store is normalized to today)
            const dateCell = r && r.date ? new Date(r.date).toLocaleDateString([], { year: 'numeric', month: '2-digit', day: '2-digit' }) : new Date().toLocaleDateString([], { year: 'numeric', month: '2-digit', day: '2-digit' });
            return `
                <tr>
                    <td>${dateCell}</td>
                    <td>${item.employeeNo || '-'}</td>
                    <td>${item.name}</td>
                    <td>${r.time_in_1 || '-'}</td>
                    <td>${r.time_out_1 || '-'}</td>
                    <td>${r.time_in_2 || '-'}</td>
                    <td>${r.time_out_2 || '-'}</td>
                    <td>${r.time_in_3 || '-'}</td>
                    <td>${r.time_out_3 || '-'}</td>
                    <td>${r.time_in_4 || '-'}</td>
                    <td>${r.time_out_4 || '-'}</td>
                </tr>
            `;
        }).join('');
    }

    function openTodayRecordsModal() {
        const modalEl = document.getElementById('todayRecordsModal');
        if (!modalEl) return;
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
        // focus search
        const search = modalEl.querySelector('#today-records-search');
        if (search) {
            search.value = '';
            search.focus();
            renderTodayRecordsList('');
            // use oninput to avoid attaching multiple listeners on repeated opens
            search.oninput = function() { renderTodayRecordsList(this.value); };
        } else {
            renderTodayRecordsList('');
        }
    }

    const notificationModalEl = document.getElementById('prototypeNotificationModal');
    const notificationModal = notificationModalEl ? new bootstrap.Modal(notificationModalEl) : null;
    const notificationTitle = document.getElementById('prototypeNotificationTitle');
    const notificationMessage = document.getElementById('prototypeNotificationMessage');
    const notificationIcon = document.getElementById('prototypeNotificationIcon');

    async function fetchFingerprint(path, options = {}) {
        const servers = [activeFingerprintServer];
        const alternate = activeFingerprintServer === FINGERPRINT_SERVER_PRIMARY ? FINGERPRINT_SERVER_FALLBACK : FINGERPRINT_SERVER_PRIMARY;

        if (!servers.includes(alternate)) {
            servers.push(alternate);
        }

        let lastError = null;
        for (const server of servers) {
            try {
                const response = await fetch(`${server}${path}`, options);
                activeFingerprintServer = server;
                return response;
            } catch (error) {
                lastError = error;
            }
        }

        throw lastError || new Error('Cannot connect to fingerprint server');
    }

    async function pollServerHealth() {
        try {
            const response = await fetchFingerprint('/health');
            const data = await response.json();

            if (data?.status === 'ok' && (data?.scanner?.connected || data?.scanner?.busy)) {
                scannerStatus.className = 'badge bg-success';
                scannerStatus.innerHTML = '<i class="fas fa-check-circle"></i> Connected';
                btnScan.disabled = false;
                btnEnroll.disabled = false;
                updateStatus('ready', 'Ready to Scan', 'Place your finger on the scanner and click "Start Scanning"');
            } else {
                scannerStatus.className = 'badge bg-danger';
                scannerStatus.innerHTML = '<i class="fas fa-times-circle"></i> Scanner not connected';
                btnScan.disabled = true;
                btnEnroll.disabled = true;
                updateStatus('error', 'Not Connected', 'Cannot connect to fingerprint server');
            }
        } catch (error) {
            scannerStatus.className = 'badge bg-danger';
            scannerStatus.innerHTML = '<i class="fas fa-times-circle"></i> Disconnected';
            btnScan.disabled = true;
            btnEnroll.disabled = true;
            updateStatus('error', 'Not Connected', 'Cannot connect to fingerprint server');
        }
    }

    function showNotification({ title, message, type = 'info' }) {
        if (!notificationModal) return;

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

        notificationTitle.textContent = title || 'Notification';
        notificationMessage.textContent = message || '--';
        notificationIcon.innerHTML = `<i class="fas ${iconMap[type] || iconMap.info}"></i>`;
        notificationIcon.style.color = colorMap[type] || colorMap.info;
        notificationModal.show();
    }

    function findEmployeeByNumber(inputValue) {
        const normalized = String(inputValue || '').trim().toLowerCase();
        if (!normalized) return null;
        return employeeDirectory.find(emp => emp.employeeNo.toLowerCase() === normalized)
            || employeeDirectory.find(emp => emp.employeeNo.toLowerCase().includes(normalized));
    }

    function updateClock() {
        const now = new Date();
        document.getElementById('current-time').textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        document.getElementById('frame1-current-time').textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        document.getElementById('frame1-current-date').textContent = now.toLocaleDateString([], { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }

    function updateStatus(type, title, message) {
        scanStatus.className = `status-box status-${type === 'ready' ? 'waiting' : type}`;
        statusTitle.textContent = title;
        const icons = {
            ready: 'fa-hand-point-up',
            scanning: 'fa-circle-notch fa-spin',
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle'
        };
        statusMessage.innerHTML = `<i class="fas ${icons[type] || 'fa-info-circle'}"></i> ${message}`;
    }

    function setScanning(scanning) {
        isScanning = scanning;
        btnScan.disabled = scanning;
        btnEnroll.disabled = scanning;
        btnScan.innerHTML = scanning ? '<i class="fas fa-circle-notch fa-spin"></i> Scanning...' : '<i class="fas fa-fingerprint"></i> Start Scanning';
    }

    function showResult(type) {
        [resultWaiting, resultMatched, resultNoMatch, resultError].forEach(el => el.classList.add('d-none'));
        const panels = { waiting: resultWaiting, matched: resultMatched, nomatch: resultNoMatch, error: resultError };
        if (panels[type]) panels[type].classList.remove('d-none');
    }

    function showReadyState() {
        showResult('waiting');
        scannerVisual.className = 'scanner-container';
        updateStatus('ready', 'Ready to Scan', 'Place your finger on the scanner and click "Start Scanning"');
    }

    function resetScan() {
        setScanning(false);
        showReadyState();
    }

    function showFrame2() {
        frame1Screen.style.display = 'none';
        frame2Wrapper.classList.remove('d-none');
    }

    function proceedFromEmployeeNoInput() {
        const employee = findEmployeeByNumber(employeeNoInput.value);
        if (!employee) {
            employeeNoHint.textContent = 'Employee number not found. Please try again.';
            employeeNoHint.classList.add('text-danger');
            return;
        }

        employeeNoHint.textContent = `Employee found: ${employee.name}. Proceeding to scan frame...`;
        employeeNoHint.classList.remove('text-danger');
        enrollEmployee.value = String(employee.id);
        setActiveTodayRecord(employee.id, employee.name, employee.employeeNo);
        updateTodayRecordDisplay();
        showFrame2();
        btnScan.focus();
    }

    function updateTodayRecordDisplay() {
        if (todayRecordStore.date !== getTodayDateString()) {
            todayRecordStore = { date: getTodayDateString(), records: {} };
            currentTodayEmployeeId = null;
            todayRecordData = getEmptyTodayRecord();
            saveTodayRecordStoreToStorage(todayRecordStore);
        }

        // Update the date header
        const todayDateObj = new Date();
        todayRecordDate.textContent = todayDateObj.toLocaleDateString([], { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

        if (!todayRecordData.employeeId) {
            todayRecordContainer.classList.remove('d-none');
            todayRecordList.classList.add('d-none');
            todayRecordStatus.textContent = 'Select an employee and scan to view today\'s record.';
            return;
        }

        // Show the record list
        todayRecordContainer.classList.add('d-none');
        todayRecordList.classList.remove('d-none');

        // Update employee info with visual indicator
        todayRecordEmployeeName.textContent = todayRecordData.employeeName || '--';
        todayRecordEmployeeId.textContent = todayRecordData.employeeCode || '--';

        // Add a badge to show this is the isolated employee record
        const employeeInfoElement = todayRecordList.querySelector('[style*="display: flex"]');
        if (employeeInfoElement && !employeeInfoElement.querySelector('.employee-isolation-badge')) {
            const badge = document.createElement('span');
            badge.className = 'employee-isolation-badge';
            badge.style.cssText = `
                display: inline-block;
                background-color: #28a745;
                color: white;
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 11px;
                font-weight: 600;
                margin-left: 8px;
                white-space: nowrap;
            `;
            badge.textContent = 'Active Record';
            const divElement = todayRecordList.querySelector('div');
            if (divElement) {
                divElement.appendChild(badge);
            }
        }

        // Build table row with date, employee info, and up to 4 time-in/time-out pairs
        const tableRow = `
            <tr>
                <td>${todayDateObj.toLocaleDateString([], { year: 'numeric', month: '2-digit', day: '2-digit' })}</td>
                <td>${todayRecordData.employeeCode || '-'}</td>
                <td>${todayRecordData.employeeName || '-'}</td>
                <td>${todayRecordData.time_in_1 || '-'}</td>
                <td>${todayRecordData.time_out_1 || '-'}</td>
                <td>${todayRecordData.time_in_2 || '-'}</td>
                <td>${todayRecordData.time_out_2 || '-'}</td>
                <td>${todayRecordData.time_in_3 || '-'}</td>
                <td>${todayRecordData.time_out_3 || '-'}</td>
                <td>${todayRecordData.time_in_4 || '-'}</td>
                <td>${todayRecordData.time_out_4 || '-'}</td>
            </tr>
        `;
        todayRecordTbody.innerHTML = tableRow;
    }

    function logScanEvent(employeeId, employeeName, employeeCode, eventType, status) {
        setActiveTodayRecord(employeeId, employeeName, employeeCode);

        if (!todayRecordData.employeeId) {
            todayRecordData.employeeId = employeeId;
            todayRecordData.employeeName = employeeName;
            todayRecordData.employeeCode = employeeCode;
        }

        // Get current time
        const now = new Date();
        const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });

        // Auto-determine action type based on existing records if not explicitly set
        let actionType = eventType;
        if (eventType === 'Time In' || eventType === 'Time Out') {
            // Check if we need to force time_out (if last time_in has no time_out)
            if (todayRecordData.time_in_1 && !todayRecordData.time_out_1) {
                actionType = 'Time Out';
            } else if (todayRecordData.time_in_2 && !todayRecordData.time_out_2) {
                actionType = 'Time Out';
            } else if (todayRecordData.time_in_3 && !todayRecordData.time_out_3) {
                actionType = 'Time Out';
            } else if (todayRecordData.time_in_4 && !todayRecordData.time_out_4) {
                actionType = 'Time Out';
            }
            // If all pairs are complete or no time_in yet, it should be time_in
            else if (todayRecordData.time_out_1 && todayRecordData.time_out_2 && todayRecordData.time_out_3 && todayRecordData.time_out_4) {
                // All 4 pairs are complete
                return;
            }
        }

        // Log based on determined action type
        if (actionType === 'Time In') {
            // Find first empty time_in slot
            if (!todayRecordData.time_in_1) {
                todayRecordData.time_in_1 = timeStr;
            } else if (!todayRecordData.time_in_2) {
                todayRecordData.time_in_2 = timeStr;
            } else if (!todayRecordData.time_in_3) {
                todayRecordData.time_in_3 = timeStr;
            } else if (!todayRecordData.time_in_4) {
                todayRecordData.time_in_4 = timeStr;
            }
        } else if (actionType === 'Time Out') {
            // Fill corresponding time_out slot based on which time_in was last filled
            if (todayRecordData.time_in_1 && !todayRecordData.time_out_1) {
                todayRecordData.time_out_1 = timeStr;
            } else if (todayRecordData.time_in_2 && !todayRecordData.time_out_2) {
                todayRecordData.time_out_2 = timeStr;
            } else if (todayRecordData.time_in_3 && !todayRecordData.time_out_3) {
                todayRecordData.time_out_3 = timeStr;
            } else if (todayRecordData.time_in_4 && !todayRecordData.time_out_4) {
                todayRecordData.time_out_4 = timeStr;
            }
        }

        // Update the display and persist to storage
        updateTodayRecordDisplay();
        persistCurrentEmployeeRecord();
    }

    function refreshTodayRecord() {
        if (currentTodayEmployeeId) {
            const storedRecord = getEmployeeRecordFromStore(currentTodayEmployeeId);
            if (storedRecord) {
                todayRecordData = {
                    ...getEmptyTodayRecord(storedRecord.employeeId, storedRecord.employeeName, storedRecord.employeeCode),
                    ...storedRecord,
                    date: getTodayDateString()
                };
            }
        }
        updateTodayRecordDisplay();
    }

    async function startScan() {
        try {
            setScanning(true);
            showResult('waiting');
            scannerVisual.className = 'scanner-container scanning';
            updateStatus('scanning', 'Scanning...', 'Place your finger on the scanner');

            const response = await fetchFingerprint('/api/verify', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    allowAttendance: true,
                    employeeId: enrollEmployee.value ? parseInt(enrollEmployee.value) : null,
                    preferSelectedEmployee: true,
                })
            });

            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Failed to verify fingerprint');
            }

            handleVerifyResult(data);
        } catch (error) {
            console.error('Scan error:', error);
            handleError(error.message);
        }
    }

    async function enrollFingerprint() {
        const employeeId = enrollEmployee.value;
        if (!employeeId) {
            showNotification({ title: 'Select Employee', message: 'Please select an employee first.', type: 'warning' });
            return;
        }

        try {
            setScanning(true);
            scannerVisual.className = 'scanner-container scanning';
            updateStatus('scanning', 'Enrolling...', 'Place your finger on the scanner');

            const response = await fetchFingerprint('/api/enroll', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ employeeId: parseInt(employeeId) })
            });

            const data = await response.json();
            handleEnrollResult(data);
        } catch (error) {
            console.error('Enroll error:', error);
            handleError(error.message);
        }
    }

    function handleVerifyResult(data) {
        setScanning(false);
        if (data.matched) {
            if (revertTimer) {
                clearTimeout(revertTimer);
                revertTimer = null;
            }

            scannerVisual.className = 'scanner-container success';
            updateStatus('success', 'Verified!', `Welcome, ${data.employee.employee_name || data.employee.name}!`);

            document.getElementById('employee-name').textContent = data.employee.employee_name || data.employee.name;
            document.getElementById('employee-id').textContent = data.employee.employee_code || data.employee.employee_id;
            document.getElementById('attendance-message').querySelector('span').textContent = data.attendance?.message || data.message || 'Attendance recorded';
            document.getElementById('match-score').textContent = data.matchScore ? `Match score: ${data.matchScore}%` : 'Match score: 100%';
            if (verifiedTime) {
                verifiedTime.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            }
            if (verifiedDate) {
                verifiedDate.textContent = new Date().toLocaleDateString([], { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            }

            showNotification({
                title: data.attendance?.action === 'time_out' ? 'Time Out Recorded' : 'Time In Recorded',
                message: data.attendance?.message || data.message || 'Attendance recorded successfully.',
                type: 'success',
            });

            // Log the scan event to today's record
            const scannedEmployeeId = data.employee.id || enrollEmployee.value;
            const scannedEmployeeName = data.employee.employee_name || data.employee.name;
            const scannedEmployeeCode = data.employee.employee_code || data.employee.employee_id;
            
            // Verify we have valid scanned employee data
            if (!scannedEmployeeId || !scannedEmployeeName || !scannedEmployeeCode) {
                console.error('Invalid employee data from fingerprint scan', data.employee);
                showNotification({
                    title: 'Data Error',
                    message: 'Employee data is incomplete. Please try again.',
                    type: 'danger'
                });
                return;
            }
            
            logScanEvent(
                scannedEmployeeId,
                scannedEmployeeName,
                scannedEmployeeCode,
                data.attendance?.action === 'time_out' ? 'Time Out' : 'Time In',
                'success'
            );

            showResult('matched');
            revertTimer = setTimeout(() => {
                showReadyState();
            }, 10000);
        } else {
            scannerVisual.className = 'scanner-container error';
            updateStatus('error', 'Not Found', data.message || 'Fingerprint not recognized');
            showNotification({ title: 'Fingerprint Not Recognized', message: data.message || 'Fingerprint not recognized.', type: 'warning' });
            
            // Log the failed scan event
            const empId = parseInt(enrollEmployee.value);
            const empRecord = employeeDirectory.find(e => String(e.id) === String(empId));
            if (empId && empRecord) {
                logScanEvent(
                    empId,
                    empRecord.name,
                    empRecord.employeeNo,
                    'Verification Attempt',
                    'failed'
                );
            }
            
            showResult('nomatch');
        }
    }

    function handleEnrollResult(data) {
        setScanning(false);
        if (data.success) {
            scannerVisual.className = 'scanner-container success';
            updateStatus('success', 'Enrolled!', data.message);
            showNotification({ title: 'Fingerprint Enrolled', message: data.message || 'Fingerprint enrolled successfully.', type: 'success' });
            
            // Log the enrollment event
            const empId = parseInt(enrollEmployee.value);
            const empRecord = employeeDirectory.find(e => String(e.id) === String(empId));
            if (empId && empRecord) {
                logScanEvent(
                    empId,
                    empRecord.name,
                    empRecord.employeeNo,
                    'Fingerprint Enrollment',
                    'success'
                );
            }
        } else {
            scannerVisual.className = 'scanner-container error';
            updateStatus('error', 'Failed', data.message);
            showNotification({ title: 'Enrollment Failed', message: data.message || 'Unable to enroll fingerprint.', type: 'danger' });
        }
    }

    function handleError(message) {
        setScanning(false);
        scannerVisual.className = 'scanner-container error';
        updateStatus('error', 'Error', message);
        document.getElementById('error-message').textContent = message;
        showNotification({ title: 'Error', message: message || 'An error occurred.', type: 'danger' });
        showResult('error');
    }

    btnScan.addEventListener('click', startScan);
    btnEnroll.addEventListener('click', enrollFingerprint);
    btnReset.addEventListener('click', resetScan);
    btnRefresh.addEventListener('click', refreshTodayRecord);
    if (btnViewAllToday) btnViewAllToday.addEventListener('click', openTodayRecordsModal);
    btnContinueEmployee.addEventListener('click', proceedFromEmployeeNoInput);
    btnClearEmployeeNo.addEventListener('click', function() {
        employeeNoInput.value = '';
        employeeNoInput.focus();
        employeeNoHint.textContent = 'Press Enter to continue.';
        employeeNoHint.classList.remove('text-danger');
    });

    employeeNoInput.addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            proceedFromEmployeeNoInput();
        }
    });

    // Load the selected employee's record instead of clearing other employees' data
    enrollEmployee.addEventListener('change', function() {
        const selectedEmployeeId = parseInt(this.value);
        const selectedEmployee = employeeDirectory.find(emp => String(emp.id) === String(selectedEmployeeId));

        if (!selectedEmployee) {
            currentTodayEmployeeId = null;
            todayRecordData = getEmptyTodayRecord();
            updateTodayRecordDisplay();
            return;
        }

        setActiveTodayRecord(selectedEmployee.id, selectedEmployee.name, selectedEmployee.employeeNo);
    });

    document.addEventListener('DOMContentLoaded', function() {
        updateClock();
        setInterval(updateClock, 1000);
        pollServerHealth();
        setInterval(pollServerHealth, 3000);

        if (preselectEmployeeId) {
            const employee = findEmployeeByNumber(preselectEmployeeId) || employeeDirectory.find(emp => String(emp.id) === String(preselectEmployeeId));
            if (employee) {
                employeeNoInput.value = employee.employeeNo;
                employeeNoHint.textContent = 'Employee found. Click Continue to proceed.';
                btnContinueEmployee.focus();
                enrollEmployee.value = String(employee.id);
                setActiveTodayRecord(employee.id, employee.name, employee.employeeNo);
            }
        }

        updateTodayRecordDisplay();
        frame1Screen.style.display = 'flex';
        frame2Wrapper.classList.add('d-none');
        employeeNoInput.focus();
    });
</script>
@endsection
