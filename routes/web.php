<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebAuthnController;

// Redirect root to dashboard
Route::redirect('/', '/dashboard');

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Attendance Routes
Route::prefix('attendance')->name('attendance.')->group(function () {
    Route::get('/student', [AttendanceController::class, 'studentAttendance'])->name('student');
    Route::get('/student/{studentId}/calendar', [AttendanceController::class, 'studentCalendar'])->name('student.calendar');
    Route::post('/student/{studentId}/add', [AttendanceController::class, 'addStudentAttendance'])->name('student.add');
    Route::get('/employee', [AttendanceController::class, 'employeeAttendance'])->name('employee');
    Route::get('/employee/{employeeId}/calendar', [AttendanceController::class, 'employeeCalendar'])->name('employee.calendar');
    Route::post('/employee/{employeeId}/add', [AttendanceController::class, 'addEmployeeAttendance'])->name('employee.add');
    Route::get('/visitor', [AttendanceController::class, 'visitorLog'])->name('visitor');
    Route::get('/visitor/export', [AttendanceController::class, 'exportVisitorLog'])->name('visitor.export');
});

// Scan Routes
Route::prefix('scan')->name('scan.')->group(function () {
    Route::get('/student', [ScanController::class, 'studentScan'])->name('student');
    Route::post('/student', [ScanController::class, 'processStudentScan'])->name('student.process');
    Route::get('/employee', [WebAuthnController::class, 'scanPage'])->name('employee');
    Route::get('/employee/zk9500', [WebAuthnController::class, 'zk9500Page'])->name('employee.zk9500');
        Route::get('/employee/zk9500/prototype', [WebAuthnController::class, 'zk9500PrototypePage'])->name('employee.zk9500.prototype');
        Route::get('/employee/zk9500/enroll', [WebAuthnController::class, 'zk9500EnrollPage'])->name('employee.zk9500.enroll');
        Route::get('/fingerprint/health', [WebAuthnController::class, 'fingerprintHealthProxy'])->name('fingerprint.health');
});

// WebAuthn API Routes (Windows Hello Biometric)
Route::prefix('webauthn')->group(function () {
    Route::post('/register/options', [WebAuthnController::class, 'getRegistrationOptions']);
    Route::post('/register/complete', [WebAuthnController::class, 'completeRegistration']);
    Route::post('/authenticate/options', [WebAuthnController::class, 'getAuthenticationOptions']);
    Route::post('/authenticate/verify', [WebAuthnController::class, 'verifyAuthentication']);
    Route::get('/attendance/today', [WebAuthnController::class, 'getTodayAttendance']);
    Route::get('/enrolled', [WebAuthnController::class, 'getEnrolledStatus']);
    Route::post('/delete', [WebAuthnController::class, 'deleteCredential']);
});

// Schedule Routes
Route::prefix('schedule')->name('schedule.')->group(function () {
    Route::get('/', [ScheduleController::class, 'index'])->name('index');
    Route::post('/', [ScheduleController::class, 'store'])->name('store');
    Route::put('/{id}', [ScheduleController::class, 'update'])->name('update');
    Route::delete('/{id}', [ScheduleController::class, 'destroy'])->name('destroy');
});

// Visitor Routes
Route::prefix('visitor')->name('visitor.')->group(function () {
    Route::get('/register', [VisitorController::class, 'register'])->name('register');
    Route::get('/', [VisitorController::class, 'getTodaysVisitors'])->name('index');
    Route::post('/', [VisitorController::class, 'store'])->name('store');
    Route::post('/{id}/checkout', [VisitorController::class, 'checkOut'])->name('checkout');
});

// User Management Routes
Route::prefix('users')->name('users.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('/employees/print', [UserController::class, 'printEmployees'])->name('employees.print');
    Route::post('/', [UserController::class, 'store'])->name('store');
    Route::put('/{id}', [UserController::class, 'update'])->name('update');
    Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
});

// API Routes for Edit Modal Data
Route::get('/api/students/{id}', function($id) {
    return \App\Models\Student::findOrFail($id);
});

Route::get('/api/employees/{id}', function($id) {
    return \App\Models\Employee::findOrFail($id);
});

Route::get('/api/visitors/{id}', function($id) {
    return \App\Models\Visitor::findOrFail($id);
});
