@extends('layouts.app')

@section('title', 'Registration Management')

@section('content')
<div class="page-header">
    <h1>Registration Management</h1>
    <p>Manage students, employees, and visitors</p>
</div>

@if($message = session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ $message }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($message = session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ $message }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Tabs for different user types -->
<div class="row mb-4">
    <div class="col-12">
        <ul class="nav nav-tabs" id="userTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" style="color: #198754;" id="students-tab" data-bs-toggle="tab" data-bs-target="#students" type="button" role="tab" aria-controls="students" aria-selected="true">
                    <i class="fas fa-graduation-cap"></i> Students
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" style="color: #198754;" id="employees-tab" data-bs-toggle="tab" data-bs-target="#employees" type="button" role="tab" aria-controls="employees" aria-selected="false">
                    <i class="fas fa-users"></i> Employees
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" style="color: #198754;" id="visitors-tab" data-bs-toggle="tab" data-bs-target="#visitors" type="button" role="tab" aria-controls="visitors" aria-selected="false">
                    <i class="fas fa-user-tie"></i> Visitors
                </button>
            </li>
        </ul>
    </div>
</div>

<div class="tab-content" id="userTabContent">
    <!-- Students Tab -->
    <div class="tab-pane fade show active" id="students" role="tabpanel" aria-labelledby="students-tab">
        <div class="row mb-4">
            <div class="col-12">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                    <i class="fas fa-plus"></i> Add Student
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-table"></i> Student List ({{ $students->total() }} students)
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>ID</th>
                                <th>Course</th>
                                <th>Section</th>
                                <th>RFID UID</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                                <tr>
                                    <td><strong>{{ $student->full_name }}</strong></td>
                                    <td>{{ $student->student_id_number }}</td>
                                    <td>{{ $student->course ?? '-' }}</td>
                                    <td>{{ $student->section ?? '-' }}</td>
                                    <td>{{ $student->rfid_uid ?? '-' }}</td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn edit-student" title="Edit" data-id="{{ $student->id }}" data-bs-toggle="modal" data-bs-target="#editStudentModal">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" action="{{ route('users.destroy', $student->id) }}" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="user_type" value="student">
                                                <button type="submit" class="action-btn delete" title="Delete" onclick="return confirm('Are you sure?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 30px; color: #999;">
                                        <i class="fas fa-inbox"></i> No students found
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 20px;">
                    {{ $students->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Employees Tab -->
    <div class="tab-pane fade" id="employees" role="tabpanel" aria-labelledby="employees-tab">
        <div class="row mb-4">
            <div class="col-12 d-flex gap-2 flex-wrap">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                    <i class="fas fa-plus"></i> Add Employee
                </button>

                <a href="{{ route('users.index', array_merge(request()->query(), ['tab' => 'employees', 'show_all_employees' => 1, 'new_employee_id' => null])) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-list"></i> Display All Employees
                </a>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('users.index') }}" class="row g-2 align-items-end">
                    <input type="hidden" name="tab" value="employees">
                    <input type="hidden" name="show_all_employees" value="1">

                    <div class="col-md-5">
                        <label class="form-label">Search</label>
                        <input
                            type="text"
                            class="form-control"
                            name="employee_search"
                            placeholder="Name, Employee ID, Department, Email"
                            value="{{ $employeeFilters['search'] ?? '' }}"
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-control" name="employee_status">
                            <option value="">All Status</option>
                            <option value="active" {{ ($employeeFilters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ ($employeeFilters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="{{ route('users.employees.print', ['employee_search' => $employeeFilters['search'] ?? '', 'employee_status' => $employeeFilters['status'] ?? '']) }}" target="_blank" class="btn btn-outline-dark">
                            <i class="fas fa-file-pdf"></i> Printable List (PDF)
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-table"></i>
                @if($newEmployeeId)
                    Newly Saved Employee ({{ $employees->total() }} record)
                @elseif($shouldShowEmployeeList)
                    Employee List ({{ $employees->total() }} employees)
                @else
                    Employee List Hidden
                @endif
            </div>
            <div class="card-body">
                @if(!$shouldShowEmployeeList)
                    <div style="text-align: center; padding: 30px; color: #999;">
                        <i class="fas fa-info-circle"></i>
                        Employee list is hidden by default. Click "Display All Employees" or use Search.
                    </div>
                @else
                    <div class="table-container">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>ID</th>
                                    <th>Department</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employees as $employee)
                                    <tr>
                                        <td><strong>{{ $employee->full_name }}</strong></td>
                                        <td>{{ $employee->employee_id_number }}</td>
                                        <td>{{ $employee->department ?? '-' }}</td>
                                        <td>{{ $employee->email ?? '-' }}</td>
                                        <td>{{ $employee->phone ?? '-' }}</td>
                                        <td>
                                            @if(($employee->status ?? 'active') === 'active')
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="action-btn edit-employee" title="Edit" data-id="{{ $employee->id }}" data-bs-toggle="modal" data-bs-target="#editEmployeeModal">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="{{ route('scan.employee.zk9500.enroll', ['employee_id' => $employee->employee_id_number]) }}" class="action-btn" title="Scan Fingerprint" style="background:#198754;color:#fff;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;">
                                                    <i class="fas fa-fingerprint"></i>
                                                </a>
                                                <a href="/schedule?employee_id={{ $employee->id }}" class="action-btn" title="Schedule" style="background:#198754;color:#fff;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;">
                                                    <i class="fas fa-calendar-alt"></i>
                                                </a>
                                                <form method="POST" action="{{ route('users.destroy', $employee->id) }}" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="user_type" value="employee">
                                                    <button type="submit" class="action-btn delete" title="Delete" onclick="return confirm('Delete this employee? This is allowed only when status is Inactive.')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 30px; color: #999;">
                                            <i class="fas fa-inbox"></i> No employees found
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top: 20px;">
                        {{ $employees->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Visitors Tab -->
    <div class="tab-pane fade" id="visitors" role="tabpanel" aria-labelledby="visitors-tab">
        <div class="row mb-4">
            <div class="col-12">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVisitorModal">
                    <i class="fas fa-plus"></i> Add Visitor
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-table"></i> Visitor Log ({{ $visitors->total() }} visitors)
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Person to Visit</th>
                                <th>Purpose</th>
                                <th>Date</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
@forelse($visitors as $visitor)
    <tr>
        <td><strong>{{ $visitor->full_name }}</strong></td>
        <td>{{ $visitor->phone ?? '-' }}</td>
        <td>{{ $visitor->person_to_visit_name ?? '-' }}</td>
        <td>{{ $visitor->purpose }}</td>
        <td>{{ $visitor->date ? $visitor->date->format('M d, Y') : '-' }}</td>

        <td>
            {{ $visitor->time_in
                ? \Carbon\Carbon::parse($visitor->time_in)->format('h:i A')
                : '-'
            }}
        </td>

        <td>
            {{ $visitor->time_out
                ? \Carbon\Carbon::parse($visitor->time_out)->format('h:i A')
                : '-'
            }}
        </td>

        <td>
            @if($visitor->time_out)
                <span style="background-color:#d4edda;color:#155724;padding:4px 8px;border-radius:3px;font-size:12px;font-weight:600;">
                    Checked Out
                </span>
            @else
                <span style="background-color:#cfe2ff;color:#084298;padding:4px 8px;border-radius:3px;font-size:12px;font-weight:600;">
                    Checked In
                </span>
            @endif
        </td>

        <td>
            <div class="action-buttons">
                <button class="action-btn edit-visitor" title="Edit"
                    data-id="{{ $visitor->id }}"
                    data-bs-toggle="modal"
                    data-bs-target="#editVisitorModal">
                    <i class="fas fa-edit"></i>
                </button>

                <form method="POST" action="{{ route('users.destroy', $visitor->id) }}" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="user_type" value="visitor">
                    <button type="submit" class="action-btn delete"
                        title="Delete"
                        onclick="return confirm('Are you sure?')">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="9" style="text-align:center;padding:30px;color:#999;">
            <i class="fas fa-inbox"></i> No visitors found
        </td>
    </tr>
@endforelse
</tbody>

                    </table>
                </div>
                <div style="margin-top: 20px;">
                    {{ $visitors->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('users.store') }}">
                @csrf
                <input type="hidden" name="user_type" value="student">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" placeholder="Enter first name" required>
                            @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" placeholder="Enter last name" required>
                            @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Student ID Number *</label>
                        <input type="text" name="student_id_number" class="form-control @error('student_id_number') is-invalid @enderror" placeholder="e.g., STU-2024-001" required>
                        @error('student_id_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Course</label>
                            <input type="text" name="course" class="form-control @error('course') is-invalid @enderror" placeholder="e.g., CS-101">
                            @error('course') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Section</label>
                            <input type="text" name="section" class="form-control @error('section') is-invalid @enderror" placeholder="e.g., A1">
                            @error('section') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">RFID UID</label>
                        <input type="text" name="rfid_uid" class="form-control @error('rfid_uid') is-invalid @enderror" placeholder="e.g., RF-001234">
                        @error('rfid_uid') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Schedule Section -->
                    <hr>
                    <h6 class="mb-3"><i class="fas fa-calendar"></i> Select Existing Schedule (Optional)</h6>
                    
                    <div class="mb-3">
                        <label class="form-label">Schedule Template</label>
                        <select name="schedule_id" class="form-control @error('schedule_id') is-invalid @enderror">
                            <option value="">-- No schedule (assign later) --</option>
                            @forelse($scheduleTemplates ?? [] as $schedule)
                                <option value="{{ $schedule->id }}">
                                    {{ $schedule->employee?->full_name ?? $schedule->student?->full_name ?? 'Schedule' }} - {{ $schedule->days_display }} | 
                                    {{ date('h:i A', strtotime($schedule->start_time)) }} - {{ date('h:i A', strtotime($schedule->end_time)) }}
                                    @php
                                        $start = \Carbon\Carbon::parse($schedule->start_time);
                                        $end = \Carbon\Carbon::parse($schedule->end_time);
                                        $hours = $end->diffInHours($start);
                                    @endphp
                                    ({{ $hours }}h)
                                </option>
                            @empty
                                <option value="" disabled>No schedule templates available - create one in Schedule Management first</option>
                            @endforelse
                        </select>
                        @error('schedule_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <small class="form-text text-muted">Select a schedule template to assign to this student, or leave empty to assign later from Schedule Management.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editStudentForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="user_type" value="student">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" id="edit_first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" id="edit_last_name" class="form-control" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Course</label>
                            <input type="text" name="course" id="edit_course" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Section</label>
                            <input type="text" name="section" id="edit_section" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Employee Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('users.store') }}">
                @csrf
                <input type="hidden" name="user_type" value="employee">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" placeholder="Enter first name" required>
                            @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" placeholder="Enter last name" required>
                            @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Employee ID Number *</label>
                        <input type="text" name="employee_id_number" class="form-control @error('employee_id_number') is-invalid @enderror" value="{{ old('employee_id_number', $nextEmployeeId ?? '') }}" readonly>
                        @error('employee_id_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <small class="form-text text-muted">Automatically generated in ascending order.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" class="form-control @error('department') is-invalid @enderror" placeholder="e.g., Administration">
                        @error('department') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="e.g., employee@company.com">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" placeholder="e.g., +1-555-0001">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-control @error('status') is-invalid @enderror" required>
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Schedule Section -->
                    <hr>
                    <h6 class="mb-3"><i class="fas fa-calendar"></i> Select Existing Schedule (Optional)</h6>
                    
                    <div class="mb-3">
                        <label class="form-label">Schedule Template</label>
                        <select name="schedule_id" class="form-control @error('schedule_id') is-invalid @enderror">
                            <option value="">-- No schedule (assign later) --</option>
                            @forelse($scheduleTemplates ?? [] as $schedule)
                                <option value="{{ $schedule->id }}">
                                    {{ $schedule->employee?->full_name ?? $schedule->student?->full_name ?? 'Schedule' }} - {{ $schedule->days_display }} | 
                                    {{ date('h:i A', strtotime($schedule->start_time)) }} - {{ date('h:i A', strtotime($schedule->end_time)) }}
                                    @php
                                        $start = \Carbon\Carbon::parse($schedule->start_time);
                                        $end = \Carbon\Carbon::parse($schedule->end_time);
                                        $hours = $end->diffInHours($start);
                                    @endphp
                                    ({{ $hours }}h)
                                </option>
                            @empty
                                <option value="" disabled>No schedule templates available - create one in Schedule Management first</option>
                            @endforelse
                        </select>
                        @error('schedule_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <small class="form-text text-muted">Select a schedule template to assign to this employee, or leave empty to assign later from Schedule Management.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Employee Modal -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editEmployeeForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="user_type" value="employee">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" id="edit_emp_first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" id="edit_emp_last_name" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" id="edit_emp_department" class="form-control">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="edit_emp_email" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" id="edit_emp_phone" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select name="status" id="edit_emp_status" class="form-control" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Visitor Modal -->
<div class="modal fade" id="addVisitorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Visitor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('users.store') }}">
                @csrf
                <input type="hidden" name="user_type" value="visitor">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror" placeholder="Enter full name" required>
                            @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" placeholder="e.g., +1-555-0001">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Person to Visit</label>
                        <select name="person_to_visit" class="form-control @error('person_to_visit') is-invalid @enderror">
                            <option value="">Select employee</option>
                            @forelse($visitorEmployees ?? [] as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->full_name }} - {{ $employee->department ?? 'No Department' }}</option>
                            @empty
                                <option value="" disabled>No employees available</option>
                            @endforelse
                        </select>
                        @error('person_to_visit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Purpose of Visit *</label>
                        <input type="text" name="purpose" class="form-control @error('purpose') is-invalid @enderror" placeholder="e.g., Meeting, Delivery, etc." required>
                        @error('purpose') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date *</label>
                            <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" required>
                            @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Check In Time *</label>
                            <input type="time" name="time_in" class="form-control @error('time_in') is-invalid @enderror" required>
                            @error('time_in') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Visitor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Visitor Modal -->
<div class="modal fade" id="editVisitorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Visitor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editVisitorForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="user_type" value="visitor">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="full_name" id="edit_visitor_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" id="edit_visitor_phone" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Person to Visit</label>
                        <select name="person_to_visit" id="edit_visitor_person" class="form-control">
                            <option value="">Select employee</option>
                            @forelse($visitorEmployees ?? [] as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->full_name }} - {{ $employee->department ?? 'No Department' }}</option>
                            @empty
                                <option value="" disabled>No employees available</option>
                            @endforelse
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Purpose of Visit *</label>
                        <input type="text" name="purpose" id="edit_visitor_purpose" class="form-control" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date *</label>
                            <input type="date" name="date" id="edit_visitor_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Check In Time *</label>
                            <input type="time" name="time_in" id="edit_visitor_time_in" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Check Out Time</label>
                        <input type="time" name="time_out" id="edit_visitor_time_out" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Visitor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.edit-student').forEach(btn => {
    btn.addEventListener('click', async function() {
        const studentId = this.dataset.id;
        const response = await fetch(`/api/students/${studentId}`);
        const student = await response.json();
        
        document.getElementById('edit_first_name').value = student.first_name;
        document.getElementById('edit_last_name').value = student.last_name;
        document.getElementById('edit_course').value = student.course || '';
        document.getElementById('edit_section').value = student.section || '';
        
        const form = document.getElementById('editStudentForm');
        form.action = `/users/${studentId}`;
    });
});

document.querySelectorAll('.edit-employee').forEach(btn => {
    btn.addEventListener('click', async function() {
        const employeeId = this.dataset.id;
        const response = await fetch(`/api/employees/${employeeId}`);
        const employee = await response.json();
        
        document.getElementById('edit_emp_first_name').value = employee.first_name;
        document.getElementById('edit_emp_last_name').value = employee.last_name;
        document.getElementById('edit_emp_department').value = employee.department || '';
        document.getElementById('edit_emp_email').value = employee.email || '';
        document.getElementById('edit_emp_phone').value = employee.phone || '';
        document.getElementById('edit_emp_status').value = employee.status || 'active';
        
        const form = document.getElementById('editEmployeeForm');
        form.action = `/users/${employeeId}`;
    });
});

document.querySelectorAll('.edit-visitor').forEach(btn => {
    btn.addEventListener('click', async function() {
        const visitorId = this.dataset.id;
        const response = await fetch(`/api/visitors/${visitorId}`);
        const visitor = await response.json();
        
        document.getElementById('edit_visitor_name').value = visitor.full_name;
        document.getElementById('edit_visitor_phone').value = visitor.phone || '';
        document.getElementById('edit_visitor_person').value = visitor.person_to_visit || '';
        document.getElementById('edit_visitor_purpose').value = visitor.purpose;
        document.getElementById('edit_visitor_date').value = visitor.date;
        document.getElementById('edit_visitor_time_in').value = visitor.time_in.substring(0, 5);
        document.getElementById('edit_visitor_time_out').value = visitor.time_out ? visitor.time_out.substring(0, 5) : '';
        
        const form = document.getElementById('editVisitorForm');
        form.action = `/users/${visitorId}`;
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab');

    if (!tab) {
        return;
    }

    const tabButton = document.getElementById(`${tab}-tab`);
    if (tabButton) {
        const bsTab = new bootstrap.Tab(tabButton);
        bsTab.show();
    }
});
</script>
@endsection
