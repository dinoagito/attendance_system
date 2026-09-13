@extends('layouts.public')

@section('title', 'Fingerprint Scanner - ZK9500')

@section('content')
<button id="kiosk-fullscreen-prompt" class="btn btn-dark btn-sm d-none" title="Press F to enter fullscreen"><i class="fas fa-expand"></i> Enter Fullscreen (Press F)</button>
<div id="kiosk-fullscreen-hint"><i class="fas fa-info-circle"></i> Kiosk mode — press <kbd>F</kbd> for fullscreen | <kbd>Enter</kbd> Continue | <kbd>Space</kbd> Scan</div>
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
                <div class="clock-kicker">ZK9500 Scanner</div>
                <div id="frame1-current-time" class="frame1-current-time">--:--:--</div>
                <div id="frame1-current-date" class="frame1-current-date">--- --, ----</div>
            </div>

            <div class="frame1-panel">
                <h1 class="frame-title">Search Employee No.</h1>
                <p class="frame-subtitle">Enter your employee number to begin scanning.</p>

                <div class="mb-3">
                    <label for="employee-no-input" class="form-label fw-semibold">Employee No.</label>
                    <input id="employee-no-input" class="form-control form-control-lg" type="text" placeholder="e.g., EMP-2026-004" list="employee-no-suggestions" autocomplete="off">
                   
                    <small id="employee-no-hint" class="text-muted">Press Enter to continue.</small>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button id="btn-continue-employee" type="button" class="btn btn-primary btn-lg flex-fill">Continue</button>
                    <button id="btn-clear-employee-no" type="button" class="btn btn-secondary btn-lg">Clear</button>
                </div>
            </div>
            <div class="frame1-today-wrapper" style="grid-column: 1 / -1;">
                <button class="btn btn-outline-light btn-sm w-100 d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#frame1-today-collapse" aria-expanded="true" style="background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.3); color: white;">
                    <span><i class="fas fa-calendar-alt"></i> Today's Record</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div id="frame1-today-collapse" class="collapse show mt-3">
                    <div class="card" style="margin-bottom:0;">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-calendar-alt"></i> Today's Record</span>
                            <div class="d-flex gap-2 align-items-center">
                                <button id="btn-display-all" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-users"></i> Display All Employees
                                </button>
                                <button id="btn-view-all-today" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-list"></i> View All (Modal)
                                </button>
                                <button id="btn-refresh" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-sync-alt"></i> Refresh
                                </button>
                            </div>
                        </div>
                        <div class="card-body" style="max-height: 320px; overflow-y: auto;">
                            <div id="today-record-search-wrap" class="mb-3 d-none">
                                <input id="today-record-search-main" type="search" class="form-control form-control-sm" placeholder="Search employee by name...">
                            </div>
                            <div id="today-record-container">
                                <div class="text-center text-muted py-4">
                                    <p class="mb-2"><i class="fas fa-calendar-check"></i> <strong id="today-date">-- -- ----</strong></p>
                                    <p class="mb-0" id="today-record-status">No scans recorded yet.</p>
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
    </div>

    <!-- Fingerprint Scanning Modal - appears after entering employee number, contains image, auto-ready without Space -->
    <div class="modal fade" id="scannerModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-fingerprint"></i> Scan Fingerprint - <span id="modal-employee-name">--</span> <small class="text-muted" id="modal-employee-id">--</small></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center" id="scanner-modal-body">
                    <div id="scanner-visual-modal" class="scanner-container mb-4" style="margin: 0 auto;">
                        <div class="scanner-icon"><i class="fas fa-fingerprint"></i></div>
                        <div class="scanner-ring"></div>
                    </div>
                    <div id="scan-status-modal" class="status-box status-waiting mb-4">
                        <h5 id="status-title-modal">Ready to Scan</h5>
                        <p id="status-message-modal" class="mb-0">
                            <i class="fas fa-hand-point-up"></i>
                            Place your finger on the scanner — scanning will start automatically
                        </p>
                    </div>
                    <div id="result-matched-modal" class="d-none text-center">
                        <div class="verified-banner mb-4">
                            <div class="verified-badge">
                                <i class="fas fa-check-circle"></i>
                                <span>Identity Verified</span>
                            </div>
                            <div class="match-score-chip" id="match-score-modal">Identity Match: 100%</div>
                        </div>
                        <div class="verified-hero mb-4">
                            <div class="verified-hero-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="verified-hero-copy">
                                <h4 id="employee-name-modal" class="mb-1">--</h4>
                                <p id="employee-id-modal" class="text-muted mb-0">--</p>
                            </div>
                        </div>
                        <div class="verified-date-time text-center">
                            <div id="verified-time-modal" class="verified-time">--:--:--</div>
                            <div id="verified-date-modal" class="verified-date">--- --, ----</div>
                        </div>
                        <div class="alert alert-info text-center mt-4" id="attendance-message-modal">
                            <i class="fas fa-info-circle"></i> <span>--</span>
                        </div>
                    </div>
                    <div id="result-nomatch-modal" class="d-none mt-2">
                        <div class="alert alert-danger mb-0 text-center">
                            <h6 class="mb-1"><i class="fas fa-times-circle"></i> Fingerprint Not Recognized</h6>
                            <p class="mb-0" id="nomatch-message-modal">Fingerprint not recognized</p>
                        </div>
                    </div>
                    <div id="result-error-modal" class="d-none mt-2">
                        <div class="alert alert-warning mb-0 text-center">
                            <h6 class="mb-1"><i class="fas fa-exclamation-triangle"></i> Error</h6>
                            <p class="mb-0" id="error-message-modal">An error occurred</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <small class="text-muted" id="modal-bottom-hint"><i class="fas fa-info-circle"></i> Scanning starts automatically — keep finger on scanner until verification completes</small>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div id="frame2-wrapper" class="d-none">
        <div class="page-header prototype-header">
            <h1><i class="fas fa-fingerprint"></i> Employee Biometric Attendance</h1>
            <p>ZK9500 Scanner — Public attendance scanning only. Enrollment is via Registration (Admin).</p>
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

                                <div class="d-grid gap-3">
                                    <button id="btn-scan" class="btn btn-primary kiosk-scan-btn" disabled style="padding: 22px 36px; font-size: 1.55rem; font-weight: 700; border-radius: 14px; height: 84px; letter-spacing: 0.3px; box-shadow: 0 10px 28px rgba(0,129,2,0.25);">
                                        <i class="fas fa-fingerprint" style="font-size: 1.7rem; margin-right: 10px;"></i> Start Scanning
                                        <small style="display:block; font-size:0.75rem; font-weight:500; opacity:0.9; margin-top:4px;">Press Space to scan</small>
                                    </button>
                                    <button id="btn-reset" class="btn btn-outline-secondary">
                                        <i class="fas fa-redo-alt"></i> Reset
                                    </button>
                                </div>
                                <small class="text-muted d-block mt-2"><i class="fas fa-keyboard"></i> Kiosk: <kbd>Enter</kbd> = Continue &nbsp;|&nbsp; <kbd>Space</kbd> = Start Scanning</small>

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

                <!-- Verification-only: employee context for strict check (Enroll is admin-only at /scan/employee/zk9500/enroll) -->
                <select id="enroll-employee" class="d-none" aria-hidden="true" tabindex="-1">
                    <option value="">-- Select Employee --</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->employee_id_number }})</option>
                    @endforeach
                </select>
                <!-- No enroll button here - strictly verification mode, no fingerprint creation -->

            </div>

            <div class="col-lg-6 mb-4">
                <div class="card mt-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-calendar-alt"></i> Today's Record</span>
                        <div class="d-flex gap-2 align-items-center">
                            <button id="btn-display-all" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-users"></i> Display All Employees
                            </button>
                            <button id="btn-view-all-today" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-list"></i> View All (Modal)
                            </button>
                            <button id="btn-refresh" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="today-record-search-wrap" class="mb-3 d-none">
                            <input id="today-record-search-main" type="search" class="form-control form-control-sm" placeholder="Search employee by name...">
                        </div>
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
                    <button type="button" id="notification-ok-btn" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
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

    .kiosk-scan-btn {
        transition: transform 0.12s ease, box-shadow 0.12s ease;
    }
    .kiosk-scan-btn:not(:disabled):hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 36px rgba(0,129,2,0.32);
    }
    .kiosk-scan-btn:not(:disabled):active {
        transform: translateY(0);
    }
    .kiosk-scan-btn:focus-visible {
        outline: 3px solid #008102;
        outline-offset: 3px;
    }
    kbd {
        background:#e9ecef; border:1px solid #ced4da; border-bottom-width:2px; padding:2px 6px; border-radius:4px; font-size:0.75rem;
    }

    /* Kiosk fullscreen optimization - fill screen, remove navigation */
    .public-topbar { display: none !important; }
    .public-content { padding: 0 !important; background: #f8fff9; }
    .public-shell { background: #f8fff9; min-height: 100vh; }
    .prototype-shell { min-height: 100vh; width: 100%; margin: 0; border-radius: 0; }
    .frame1-screen { min-height: 100vh; padding: 24px; border-radius: 0; }
    /* True kiosk - hide cursor when fullscreen, no mouse needed */
    :fullscreen { cursor: none; }
    :-webkit-full-screen { cursor: none; }
    :-moz-full-screen { cursor: none; }
    html:fullscreen body { cursor: none; }
    /* Also hide cursor after 2s in kiosk even if not fullscreen (optional) */
    body.kiosk-no-cursor { cursor: none; }
    body.kiosk-no-cursor * { cursor: none !important; }
    #kiosk-fullscreen-prompt {
        position: fixed;
        top: 16px;
        right: 16px;
        z-index: 9999;
        box-shadow: 0 8px 24px rgba(0,0,0,0.18);
    }
    #kiosk-fullscreen-hint {
        position: fixed;
        bottom: 16px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 9998;
        background: rgba(0,0,0,0.75);
        color: #fff;
        padding: 8px 14px;
        border-radius: 999px;
        font-size: 0.8rem;
        display: none;
    }
    #kiosk-fullscreen-hint.show { display: block; }

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

    // Kiosk fullscreen helpers (browser-compliant)
    let kioskHasTriedAutoFullscreen = false;
    function isKioskFullscreen() { return !!document.fullscreenElement; }
    function showKioskPrompt() {
        const btn = document.getElementById('kiosk-fullscreen-prompt');
        const hint = document.getElementById('kiosk-fullscreen-hint');
        if (btn) btn.classList.remove('d-none');
        if (hint) hint.classList.add('show');
    }
    function hideKioskPrompt() {
        const btn = document.getElementById('kiosk-fullscreen-prompt');
        const hint = document.getElementById('kiosk-fullscreen-hint');
        if (btn) btn.classList.add('d-none');
        if (hint) hint.classList.remove('show');
    }
    function attemptKioskFullscreen() {
        if (isKioskFullscreen() || kioskHasTriedAutoFullscreen) return;
        kioskHasTriedAutoFullscreen = true;
        const el = document.documentElement;
        if (el.requestFullscreen) {
            el.requestFullscreen().then(() => {
                hideKioskPrompt();
                document.body.classList.add('kiosk-no-cursor');
            }).catch(() => {
                // Browser blocked (requires gesture) — show prompt; user must press F (not Enter/Shift/Space)
                showKioskPrompt();
            });
        } else {
            showKioskPrompt();
        }
    }
    function toggleKioskFullscreen() {
        if (isKioskFullscreen()) {
            if (document.exitFullscreen) {
                document.exitFullscreen().catch(()=>{});
            }
        } else {
            const el = document.documentElement;
            if (el.requestFullscreen) {
                el.requestFullscreen().then(() => {
                    hideKioskPrompt();
                    document.body.classList.add('kiosk-no-cursor');
                }).catch(showKioskPrompt);
            }
        }
    }
    function setupKioskFullscreen() {
        const btn = document.getElementById('kiosk-fullscreen-prompt');
        if (btn) {
            btn.addEventListener('click', () => {
                toggleKioskFullscreen();
            });
        }
        // Use dedicated key F for fullscreen — not Enter/Shift/Space, not F11
        const tryOnF = (e) => {
            const active = document.activeElement;
            const isTyping = active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.isContentEditable);
            // Prevent F11's browser task-view overlay
            if (e.key === 'F11' || e.code === 'F11') {
                e.preventDefault();
                return;
            }
            if (isTyping) return;
            if ((e.key === 'f' || e.key === 'F' || e.code === 'KeyF') && !e.ctrlKey && !e.altKey && !e.metaKey) {
                e.preventDefault();
                toggleKioskFullscreen();
            }
        };
        document.addEventListener('keydown', tryOnF);
        document.addEventListener('fullscreenchange', () => {
            if (isKioskFullscreen()) { hideKioskPrompt(); document.body.classList.add('kiosk-no-cursor'); }
            else { showKioskPrompt(); document.body.classList.remove('kiosk-no-cursor'); }
        });
        // Initial auto-attempt (may be blocked, then F will enter)
        setTimeout(attemptKioskFullscreen, 500);
    }

    let activeFingerprintServer = FINGERPRINT_SERVER_PRIMARY;
    let isScanning = false;
    let modalResultShown = false;

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
    let retryTimer = null;
    let preserveErrorOnRescan = false;

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

    // Main Today's Record — Display All Employees (sorted, searchable, no refresh, no DB change)
    let isMainDisplayAll = false;
    function renderMainTodayAll(filter = '') {
        ensureTodayRecordStoreForToday();
        const q = String(filter || '').trim().toLowerCase();
        const rows = employeeDirectory
            .map(emp => {
                const rec = todayRecordStore.records[String(emp.id)] || null;
                return {
                    id: emp.id,
                    name: emp.name,
                    employeeNo: emp.employeeNo || emp.employee_id_number || emp.employeeNo,
                    record: rec
                };
            })
            .filter(item => !q || item.name.toLowerCase().includes(q))
            .sort((a, b) => a.name.localeCompare(b.name, undefined, { sensitivity: 'base' }));

        // Update main card header status
        const statusEl = document.getElementById('today-record-status');
        if (statusEl) {
            if (q) {
                statusEl.textContent = rows.length ? `Showing ${rows.length} matching employee(s) for "${filter}"` : `No employees match "${filter}"`;
            }
        }

        // Render into main table body (today-record-tbody) or show empty
        const tbody = document.getElementById('today-record-tbody');
        const container = document.getElementById('today-record-container');
        const list = document.getElementById('today-record-list');
        const dateEl = document.getElementById('today-date');

        if (dateEl) {
            dateEl.textContent = new Date().toLocaleDateString([], { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }

        // Ensure list is visible and container hidden when showing all
        if (container) container.classList.add('d-none');
        if (list) list.classList.remove('d-none');
        // Hide single employee header (name/id) when showing all — show generic
        const nameEl = document.getElementById('today-record-employee-name');
        const idEl = document.getElementById('today-record-employee-id');
        if (nameEl) nameEl.textContent = isMainDisplayAll ? 'All Employees (sorted A–Z)' : (todayRecordData.employeeName || '--');
        if (idEl) idEl.textContent = isMainDisplayAll ? `${rows.length} employees` : (todayRecordData.employeeCode || '--');

        if (!tbody) return;

        if (rows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="11" class="text-center text-muted">No employees match your search.</td></tr>`;
            return;
        }

        tbody.innerHTML = rows.map(item => {
            const r = item.record || {};
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

    function toggleMainDisplayAll() {
        isMainDisplayAll = !isMainDisplayAll;
        const btn = document.getElementById('btn-display-all');
        const searchWrap = document.getElementById('today-record-search-wrap');
        const searchInput = document.getElementById('today-record-search-main');
        if (isMainDisplayAll) {
            if (btn) { btn.innerHTML = '<i class="fas fa-user"></i> Show Selected Only'; btn.classList.remove('btn-outline-secondary'); btn.classList.add('btn-secondary'); }
            if (searchWrap) searchWrap.classList.remove('d-none');
            if (searchInput) { searchInput.value = ''; searchInput.focus(); }
            renderMainTodayAll('');
        } else {
            if (btn) { btn.innerHTML = '<i class="fas fa-users"></i> Display All Employees'; btn.classList.remove('btn-secondary'); btn.classList.add('btn-outline-secondary'); }
            if (searchWrap) searchWrap.classList.add('d-none');
            if (searchInput) searchInput.value = '';
            // Return to single selected view
            updateTodayRecordDisplay();
        }
    }

    const notificationModalEl = document.getElementById('prototypeNotificationModal');
    const notificationModal = notificationModalEl ? new bootstrap.Modal(notificationModalEl) : null;
    const notificationTitle = document.getElementById('prototypeNotificationTitle');
    const notificationMessage = document.getElementById('prototypeNotificationMessage');
    const notificationIcon = document.getElementById('prototypeNotificationIcon');

    if (notificationModalEl) {
        notificationModalEl.addEventListener('shown.bs.modal', function () {
            const okBtn = document.getElementById('notification-ok-btn');
            if (okBtn) okBtn.focus();
        });
    }

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

            // Do not clobber the visible scanner status while the scanner modal is open
            // (scanning) or a result (matched/nomatch/error) is being shown. Only update
            // the status text when we are in the idle Frame2 page.
            const scModalEl = document.getElementById('scannerModal');
            const modalOpen = scModalEl && scModalEl.classList.contains('show');

            if (data?.status === 'ok' && (data?.scanner?.connected || data?.scanner?.busy)) {
                scannerStatus.className = 'badge bg-success';
                scannerStatus.innerHTML = '<i class="fas fa-check-circle"></i> Connected';
                btnScan.disabled = false;
                if (btnEnroll) btnEnroll.disabled = false;
                if (!modalOpen) updateStatus('ready', 'Ready to Scan', 'Place your finger on the scanner and press Space');
                // Ensure Start Scanning is focused for kiosk Space key
                if (frame2Wrapper && !frame2Wrapper.classList.contains('d-none')) {
                    setTimeout(() => { if (!isScanning) btnScan.focus(); }, 100);
                }
            } else {
                scannerStatus.className = 'badge bg-danger';
                scannerStatus.innerHTML = '<i class="fas fa-times-circle"></i> Scanner not connected';
                btnScan.disabled = false; // keep enabled so Space is detectable and button is focusable (kiosk)
                if (btnEnroll) btnEnroll.disabled = true;
                if (!modalOpen) updateStatus('error', 'Not Connected', 'Cannot connect to fingerprint server - Press Space to try');
                if (frame2Wrapper && !frame2Wrapper.classList.contains('d-none')) {
                    setTimeout(() => { if (!isScanning) btnScan.focus(); }, 100);
                }
            }
        } catch (error) {
            scannerStatus.className = 'badge bg-danger';
            scannerStatus.innerHTML = '<i class="fas fa-times-circle"></i> Disconnected';
            btnScan.disabled = false; // keep enabled for kiosk
            if (btnEnroll) btnEnroll.disabled = true;
            const smEl = document.getElementById('scannerModal');
            const modalOpenNow = smEl && smEl.classList.contains('show');
            if (!modalOpenNow) updateStatus('error', 'Not Connected', 'Cannot connect to fingerprint server - Press Space to try');
            if (frame2Wrapper && !frame2Wrapper.classList.contains('d-none')) {
                setTimeout(() => { if (!isScanning) btnScan.focus(); }, 100);
            }
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

    function updateStatus(type, title, message, includeModal = true) {
        const icons = {
            ready: 'fa-hand-point-up',
            scanning: 'fa-circle-notch fa-spin',
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle'
        };
        const html = `<i class="fas ${icons[type] || 'fa-info-circle'}"></i> ${message}`;
        // Update Frame2 (legacy hidden) and Modal (visible)
        scanStatus.className = `status-box status-${type === 'ready' ? 'waiting' : type}`;
        statusTitle.textContent = title;
        statusMessage.innerHTML = html;
        if (includeModal) {
            const mBox = document.getElementById('scan-status-modal');
            const mTitle = document.getElementById('status-title-modal');
            const mMsg = document.getElementById('status-message-modal');
            if (mBox) mBox.className = `status-box status-${type === 'ready' ? 'waiting' : type}`;
            if (mTitle) mTitle.textContent = title;
            if (mMsg) mMsg.innerHTML = html;
        }
    }

    function setScanning(scanning) {
        isScanning = scanning;
        btnScan.disabled = scanning;
        if (btnEnroll) btnEnroll.disabled = scanning;
        btnScan.innerHTML = scanning ? '<i class="fas fa-circle-notch fa-spin"></i> Scanning...' : '<i class="fas fa-fingerprint"></i> Start Scanning';
        // Also update modal visual
        const mv = document.getElementById('scanner-visual-modal');
        if (mv) mv.className = scanning ? 'scanner-container scanning' : 'scanner-container';
    }

    function showResult(type, options = {}) {
        const preserveErrors = !!options.preserveErrors;
        [resultWaiting, resultMatched, resultNoMatch, resultError].forEach(el => el && el.classList.add('d-none'));
        const panels = { waiting: resultWaiting, matched: resultMatched, nomatch: resultNoMatch, error: resultError };
        if (panels[type]) panels[type].classList.remove('d-none');
        // Also update modal result panels
        const rmM = document.getElementById('result-matched-modal');
        const rnM = document.getElementById('result-nomatch-modal');
        const reM = document.getElementById('result-error-modal');
        [rmM].forEach(el => el && el.classList.add('d-none'));
        // When preserving errors (auto-rescan after a failure), keep the error panels visible
        // so the previous failure is not removed until the next attempt actually returns a result.
        if (!preserveErrors) {
            [rnM, reM].forEach(el => el && el.classList.add('d-none'));
        }
        const panelsM = { matched: rmM, nomatch: rnM, error: reM };
        if (panelsM[type]) panelsM[type].classList.remove('d-none');
        // The "Ready to Scan" / scanning UI (scanner visual + status box) stays visible while
        // waiting, and also on a failed scan (nomatch/error) so the error appears at the BOTTOM
        // of the same modal while the scanner remains live. Only a successful match swaps in the
        // verified panel and hides the scan UI.
        const scVisModal = document.getElementById('scanner-visual-modal');
        const scStatusModal = document.getElementById('scan-status-modal');
        const scVis = document.getElementById('scanner-visual');
        const scStatus = document.getElementById('scan-status');
        const showScanUI = (type !== 'matched');
        if (scVisModal) scVisModal.classList.toggle('d-none', !showScanUI);
        if (scStatusModal) scStatusModal.classList.toggle('d-none', !showScanUI);
        if (scVis) scVis.classList.toggle('d-none', !showScanUI);
        if (scStatus) scStatus.classList.toggle('d-none', !showScanUI);
    }

    function showReadyState() {
        cancelAutoRescan();
        showResult('waiting');
        scannerVisual.className = 'scanner-container';
        updateStatus('ready', 'Ready to Scan', 'Place your finger on the scanner and click "Start Scanning"');
        // Also reset modal visual/status to ready
        const mv = document.getElementById('scanner-visual-modal');
        const mBox = document.getElementById('scan-status-modal');
        const mTitle = document.getElementById('status-title-modal');
        const mMsg = document.getElementById('status-message-modal');
        if (mv) mv.className = 'scanner-container';
        if (mBox) mBox.className = 'status-box status-waiting';
        if (mTitle) mTitle.textContent = 'Ready to Scan';
        if (mMsg) mMsg.innerHTML = '<i class="fas fa-hand-point-up"></i> Place your finger on the scanner — scanning will start automatically';
    }

    function resetScan() {
        setScanning(false);
        showReadyState();
    }

    function showFrame2() {
        frame1Screen.style.display = 'none';
        frame2Wrapper.classList.remove('d-none');
    }

    function showFrame1() {
        // Hide scanner modal if open (modal type, not page)
        const scModalEl = document.getElementById('scannerModal');
        if (scModalEl) {
            const inst = bootstrap.Modal.getInstance(scModalEl);
            if (inst) inst.hide();
        }
        frame2Wrapper.classList.add('d-none');
        frame1Screen.style.display = 'flex';
        employeeNoInput.value = '';
        employeeNoHint.textContent = 'Press Enter to continue.';
        employeeNoHint.classList.remove('text-danger');
        setScanning(false);
        modalResultShown = false;
        showReadyState();
        // Ensure Today's Record collapsible in Frame1 is visible and updated
        const coll = document.getElementById('frame1-today-collapse');
        if (coll && !coll.classList.contains('show')) {
            new bootstrap.Collapse(coll, { toggle: false }).show();
        }
        // Focus input for next employee (kiosk continuous) and clear for next
        setTimeout(() => employeeNoInput.focus(), 150);
    }

    function autoReturnToSearch(delayMs = 4000) {
        if (revertTimer) {
            clearTimeout(revertTimer);
            revertTimer = null;
        }
        revertTimer = setTimeout(() => {
            if (notificationModalEl && notificationModalEl.classList.contains('show') && notificationModal) {
                notificationModal.hide();
                // Wait for modal hide animation before switching frames
                setTimeout(showFrame1, 300);
            } else {
                showFrame1();
            }
        }, delayMs);
    }

    function cancelAutoRescan() {
        if (retryTimer) {
            clearTimeout(retryTimer);
            retryTimer = null;
        }
    }

    // After a failed scan, the kiosk stays on the scanning modal and automatically starts
    // a new scan after a short delay so the user can rescan immediately.
    function scheduleAutoRescan(delayMs = 1200) {
        cancelAutoRescan();
        retryTimer = setTimeout(() => {
            retryTimer = null;
            const scModalEl = document.getElementById('scannerModal');
            const stillOpen = scModalEl && scModalEl.classList.contains('show');
            if (stillOpen && !isScanning) {
                modalResultShown = false;
                preserveErrorOnRescan = true;
                startScan();
            }
        }, delayMs);
    }

    function proceedFromEmployeeNoInput() {
        const employee = findEmployeeByNumber(employeeNoInput.value);
        if (!employee) {
            employeeNoHint.textContent = 'Employee number not found. Please try again.';
            employeeNoHint.classList.add('text-danger');
            return;
        }

        employeeNoHint.textContent = `Employee found: ${employee.name}. Opening scanner...`;
        employeeNoHint.classList.remove('text-danger');
        enrollEmployee.value = String(employee.id);
        setActiveTodayRecord(employee.id, employee.name, employee.employeeNo);
        updateTodayRecordDisplay();
        // Show modal instead of going to other page (Frame2) - contains image, auto-ready without Space
        const modalEl = document.getElementById('scannerModal');
        const modalNameEl = document.getElementById('modal-employee-name');
        const modalIdEl = document.getElementById('modal-employee-id');
        if (modalNameEl) modalNameEl.textContent = employee.name;
        if (modalIdEl) modalIdEl.textContent = employee.employeeNo;
        // Also set modal's employee header for verification display
        const modalEmpName = document.getElementById('employee-name-modal');
        const modalEmpId = document.getElementById('employee-id-modal');
        if (modalEmpName) modalEmpName.textContent = employee.name;
        if (modalEmpId) modalEmpId.textContent = employee.employeeNo;
        if (modalEl) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
            // Auto-start scanning when modal is shown - no Space needed, ready until finger placed
            const onShown = function() {
                modalEl.removeEventListener('shown.bs.modal', onShown);
                modalResultShown = false;
                cancelAutoRescan();
                // Reset modal scanner visual to scanning state (and re-show it if hidden from prior success)
                const mv = document.getElementById('scanner-visual-modal');
                const st = document.getElementById('status-title-modal');
                const sm = document.getElementById('status-message-modal');
                const mBox = document.getElementById('scan-status-modal');
                if (mv) { mv.classList.remove('d-none'); mv.className = 'scanner-container scanning'; }
                if (mBox) mBox.classList.remove('d-none');
                if (st) st.textContent = 'Scanning...';
                if (sm) sm.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Place your finger on the scanner — scanning will start automatically';
                const mHint = document.getElementById('modal-bottom-hint');
                if (mHint) mHint.innerHTML = '<i class="fas fa-info-circle"></i> Scanning starts automatically — keep finger on scanner until verification completes';
                // Show waiting state in modal
                const rm = document.getElementById('result-matched-modal');
                const rn = document.getElementById('result-nomatch-modal');
                const re = document.getElementById('result-error-modal');
                if (rm) rm.classList.add('d-none');
                if (rn) rn.classList.add('d-none');
                if (re) re.classList.add('d-none');
                setTimeout(() => {
                    startScan();
                }, 400);
            };
            modalEl.addEventListener('shown.bs.modal', onShown, { once: true });
        } else {
            // Fallback to old Frame2 if modal not found
            showFrame2();
            if (btnScan) btnScan.focus();
        }
    }

    function updateTodayRecordDisplay() {
        if (todayRecordStore.date !== getTodayDateString()) {
            todayRecordStore = { date: getTodayDateString(), records: {} };
            currentTodayEmployeeId = null;
            todayRecordData = getEmptyTodayRecord();
            saveTodayRecordStoreToStorage(todayRecordStore);
        }

        // If Display All is active, render all sorted and respect search filter
        if (typeof isMainDisplayAll !== 'undefined' && isMainDisplayAll) {
            const searchInput = document.getElementById('today-record-search-main');
            const filter = searchInput ? searchInput.value : '';
            renderMainTodayAll(filter);
            return;
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
        // Only one scan at a time: ignore re-entry while a scan is already in progress.
        if (isScanning) return;
        cancelAutoRescan();
        modalResultShown = false;
        // If this scan was auto-started after a failed attempt, keep the previous error
        // visible until this new attempt returns a result (do not wipe it on rescan).
        const preserveErrors = preserveErrorOnRescan;
        preserveErrorOnRescan = false;
        try {
            setScanning(true);
            showResult('waiting', { preserveErrors });
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
            const message = (error && error.message && error.message.toLowerCase().includes('failed to fetch'))
                ? 'fingerprint mismatched'
                : error.message;
            handleError(message);
        }
    }

    // Verification-only: no enrollment in kiosk (enrollment via admin Registration Management → Enroll)
    function handleVerifyResult(data) {
        setScanning(false);
        // Schedule validation: reject if no schedule today (dynamic date, not hardcoded)
        if (data.schedule_valid === false || data.attendance?.action === 'no_schedule' || (data.matched && data.success === false && data.message && data.message.toLowerCase().includes('no schedule'))) {
            scannerVisual.className = 'scanner-container error';
            updateStatus('error', 'No Schedule Today', data.message || 'No schedule for today — attendance not allowed.', false);
            const errEl = document.getElementById('error-message');
            if (errEl) errEl.textContent = data.message || 'No schedule for today.';
            const errMsgModal = document.getElementById('error-message-modal');
            if (errMsgModal) errMsgModal.textContent = data.message || 'No schedule for today.';
            showResult('error');
            modalResultShown = true;
            scheduleAutoRescan();
            return;
        }
        // Strict verification: fingerprint must belong to selected employee
        if (data.success === false && data.matched === false && data.message && data.message.toLowerCase().includes('does not belong')) {
            scannerVisual.className = 'scanner-container error';
            updateStatus('error', 'Verification Failed', data.message, false);
            const errEl2 = document.getElementById('error-message');
            if (errEl2) errEl2.textContent = data.message;
            const errMsgModal2 = document.getElementById('error-message-modal');
            if (errMsgModal2) errMsgModal2.textContent = data.message;
            showResult('error');
            modalResultShown = true;
            scheduleAutoRescan();
            return;
        }
        if (data.matched) {
            if (revertTimer) {
                clearTimeout(revertTimer);
                revertTimer = null;
            }
            cancelAutoRescan();

            // Update BOTH hidden Frame2 and visible Modal (modal is what you see)
            const empName = data.employee.employee_name || data.employee.name;
            const empCode = data.employee.employee_code || data.employee.employee_id;
            const matchText = data.matchScore ? `Identity Match: ${Math.min(data.matchScore,100)}%` : 'Identity Match: 100%';
            const timeNow = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            const dateNow = new Date().toLocaleDateString([], { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            const attendanceMsg = data.attendance?.message || data.message || 'Attendance recorded';

            // Legacy Frame2 (hidden, kept for fallback)
            scannerVisual.className = 'scanner-container success';
            updateStatus('success', 'Verified!', `Welcome, ${empName}!`);
            document.getElementById('employee-name').textContent = empName;
            document.getElementById('employee-id').textContent = empCode;
            document.getElementById('attendance-message').querySelector('span').textContent = attendanceMsg;
            document.getElementById('match-score').textContent = data.matchScore ? `Match score: ${data.matchScore}%` : 'Match score: 100%';
            if (verifiedTime) verifiedTime.textContent = timeNow;
            if (verifiedDate) verifiedDate.textContent = dateNow;

            // Visible Modal (what you see in Image 1)
            const mv = document.getElementById('scanner-visual-modal');
            if (mv) mv.className = 'scanner-container success';
            const mName = document.getElementById('employee-name-modal');
            const mId = document.getElementById('employee-id-modal');
            const mScore = document.getElementById('match-score-modal');
            const mAtt = document.getElementById('attendance-message-modal');
            const mTime = document.getElementById('verified-time-modal');
            const mDate = document.getElementById('verified-date-modal');
            if (mName) mName.textContent = empName;
            if (mId) mId.textContent = empCode;
            if (mScore) mScore.textContent = matchText;
            if (mTime) mTime.textContent = timeNow;
            if (mDate) mDate.textContent = dateNow;
            if (mAtt) mAtt.querySelector('span').textContent = attendanceMsg;

            // Show only the verified result panel in the modal; hide scanner visual/status for clean display
            const scVisModal = document.getElementById('scanner-visual-modal');
            const scStatusModal = document.getElementById('scan-status-modal');
            if (scVisModal) scVisModal.classList.add('d-none');
            if (scStatusModal) scStatusModal.classList.add('d-none');
            const bottomHint = document.getElementById('modal-bottom-hint');
            if (bottomHint) bottomHint.innerHTML = '<i class="fas fa-keyboard"></i> Press <kbd>Enter</kbd> to close and return';

            // Do NOT show covering Time In Recorded popup on success - keep Identity Verified visible inside modal
            // (was showNotification({title:'Time In Recorded'}) which covered the modal in your screenshot)

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
            modalResultShown = true;
            // Continuous kiosk: automatically return to Search Employee No. after showing result
            autoReturnToSearch(4000);
        } else {
            scannerVisual.className = 'scanner-container error';
            updateStatus('error', 'Not Found', data.message || 'Fingerprint not recognized', false);
            const nomatchMsg = document.getElementById('nomatch-message-modal');
            if (nomatchMsg) nomatchMsg.textContent = data.message || 'Fingerprint not recognized.';
            
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
            modalResultShown = true;
            scheduleAutoRescan();
        }
    }

    // handleEnrollResult removed - kiosk is strictly verification mode; enrollment is admin-only at /scan/employee/zk9500/enroll
    function handleError(message) {
        setScanning(false);
        scannerVisual.className = 'scanner-container error';
        updateStatus('error', 'Error', message, false);
        document.getElementById('error-message').textContent = message;
        const errMsgModal = document.getElementById('error-message-modal');
        if (errMsgModal) errMsgModal.textContent = message;
        showResult('error');
        modalResultShown = true;
        scheduleAutoRescan();
    }

    btnScan.addEventListener('click', startScan);
    // enrollFingerprint removed - kiosk verification-only
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

    // Kiosk keyboard controls: Enter = Continue / Confirm, Space = Start Scanning (no mouse needed) — F is for fullscreen (not Enter/Shift/Space)
    function isScanErrorVisible() {
        const reM = document.getElementById('result-error-modal');
        const rnM = document.getElementById('result-nomatch-modal');
        return (reM && !reM.classList.contains('d-none')) || (rnM && !rnM.classList.contains('d-none'));
    }

    function setupKioskKeyboardControls() {
        document.addEventListener('keydown', function(e) {
            const active = document.activeElement;
            const isTyping = active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.isContentEditable);
            if (e.key === 'Enter') {
                const scModalEl = document.getElementById('scannerModal');
                if (scModalEl && scModalEl.classList.contains('show')) {
                    // Scanner modal is open: Enter closes it (if a result/error is shown) or is ignored while scanning
                    e.preventDefault();
                    if (modalResultShown || isScanErrorVisible()) {
                        if (revertTimer) { clearTimeout(revertTimer); revertTimer = null; }
                        modalResultShown = false;
                        showFrame1();
                    }
                    return;
                }
                if (notificationModalEl && notificationModalEl.classList.contains('show')) {
                    const okBtn = document.getElementById('notification-ok-btn');
                    if (okBtn) { e.preventDefault(); okBtn.click(); }
                    return;
                }
                const frame1Visible = frame1Screen && getComputedStyle(frame1Screen).display !== 'none' && !frame1Screen.classList.contains('d-none');
                if (frame1Visible) {
                    if (active === employeeNoInput || !isTyping) {
                        e.preventDefault();
                        proceedFromEmployeeNoInput();
                    }
                    return;
                }
            }
            if (e.key === ' ' || e.code === 'Space') {
                if (isTyping) return;
                const frame2Visible = frame2Wrapper && !frame2Wrapper.classList.contains('d-none') && getComputedStyle(frame2Wrapper).display !== 'none';
                if (frame2Visible && btnScan && !btnScan.disabled && !isScanning) {
                    e.preventDefault();
                    btnScan.focus();
                    startScan();
                }
            }
        });
        // Enforce kiosk: no right-click menu, no mouse needed
        document.addEventListener('contextmenu', e => e.preventDefault());
    }

    document.addEventListener('DOMContentLoaded', function() {
        setupKioskKeyboardControls();
        setupKioskFullscreen();
        updateClock();
        setInterval(updateClock, 1000);
        pollServerHealth();
        setInterval(pollServerHealth, 3000);

        // Wire Display All Employees and live search (no refresh, no DB change)
        const btnDisplayAllInit = document.getElementById('btn-display-all');
        const searchMainInit = document.getElementById('today-record-search-main');
        if (btnDisplayAllInit) {
            btnDisplayAllInit.addEventListener('click', toggleMainDisplayAll);
        }
        if (searchMainInit) {
            searchMainInit.addEventListener('input', function() {
                if (isMainDisplayAll) {
                    renderMainTodayAll(this.value);
                }
            });
        }

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
