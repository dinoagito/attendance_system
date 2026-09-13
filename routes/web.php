<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebAuthnController;
use App\Http\Controllers\AuthController;

// Auth Routes (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Public kiosk routes (no auth required) - Biometrics (attendance), Register Visitor
Route::prefix('scan')->name('scan.')->group(function () {
    Route::get('/employee', [WebAuthnController::class, 'scanPage'])->name('employee');
    Route::get('/employee/zk9500', [WebAuthnController::class, 'zk9500Page'])->name('employee.zk9500');
    Route::get('/employee/zk9500/prototype', [WebAuthnController::class, 'zk9500PrototypePage'])->name('employee.zk9500.prototype');
});
Route::prefix('visitor')->name('visitor.')->group(function () {
    Route::get('/register', [VisitorController::class, 'register'])->name('register');
    Route::get('/', [VisitorController::class, 'getTodaysVisitors'])->name('index');
    Route::post('/', [VisitorController::class, 'store'])->name('store');
    Route::post('/{id}/checkout', [VisitorController::class, 'checkOut'])->name('checkout');
});
Route::prefix('webauthn')->group(function () {
    Route::post('/authenticate/options', [WebAuthnController::class, 'getAuthenticationOptions']);
    Route::post('/authenticate/verify', [WebAuthnController::class, 'verifyAuthentication']);
    Route::get('/attendance/today', [WebAuthnController::class, 'getTodayAttendance']);
});

// Public kiosk landing page (separate page for public flows)
Route::view('/kiosk', 'kiosk.index')->name('kiosk');

// Root - redirect to dashboard (auth middleware will redirect guests to login)
Route::redirect('/', '/dashboard');

// Admin routes (auth required) - Dashboard, Attendance logs, Schedule, Registration, Enroll
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/employee', [AttendanceController::class, 'employeeAttendance'])->name('employee');
        Route::get('/employee/print', [AttendanceController::class, 'employeeAttendancePrint'])->name('employee.print');
        Route::get('/employee/{employeeId}/calendar', [AttendanceController::class, 'employeeCalendar'])->name('employee.calendar');
        Route::post('/employee/{employeeId}/add', [AttendanceController::class, 'addEmployeeAttendance'])->name('employee.add');
        Route::get('/visitor', [AttendanceController::class, 'visitorLog'])->name('visitor');
        Route::get('/visitor/export', [AttendanceController::class, 'exportVisitorLog'])->name('visitor.export');
    });

    // Dedicated enroll page (admin only) - separate from kiosk scan
    Route::get('/scan/employee/zk9500/enroll', [WebAuthnController::class, 'zk9500EnrollPage'])->name('scan.employee.zk9500.enroll');

    // WebAuthn enrollment (admin only)
    Route::prefix('webauthn')->group(function () {
        Route::post('/register/options', [WebAuthnController::class, 'getRegistrationOptions']);
        Route::post('/register/complete', [WebAuthnController::class, 'completeRegistration']);
        Route::get('/enrolled', [WebAuthnController::class, 'getEnrolledStatus']);
        Route::post('/delete', [WebAuthnController::class, 'deleteCredential']);
    });

    Route::prefix('schedule')->name('schedule.')->group(function () {
        Route::get('/', [ScheduleController::class, 'index'])->name('index');
        Route::post('/', [ScheduleController::class, 'store'])->name('store');
        Route::put('/{id}', [ScheduleController::class, 'update'])->name('update');
        Route::delete('/{id}', [ScheduleController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/employees/print', [UserController::class, 'printEmployees'])->name('employees.print');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::put('/{id}', [UserController::class, 'update'])->name('update');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
    });

    // API Routes for Edit Modal Data (admin only) - Student removed
    Route::get('/api/employees/{id}', function($id) {
        return \App\Models\Employee::findOrFail($id);
    });

    Route::get('/api/visitors/{id}', function($id) {
        return \App\Models\Visitor::findOrFail($id);
    });
});
