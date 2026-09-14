@extends('layouts.app')

@section('title', 'Registration Management')

@section('content')
<div class="page-header">
    <h1>Registration Management - Employees / Faculty & Visitors</h1>
    <p>Manage employees, faculty and visitors</p>
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

<!-- Tabs for different user types - Student removed, Employee/Faculty and Visitors only -->
<div class="row mb-4">
    <div class="col-12">
        <ul class="nav nav-tabs" id="userTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" style="color: #198754;" id="employees-tab" data-bs-toggle="tab" data-bs-target="#employees" type="button" role="tab" aria-controls="employees" aria-selected="true">
                    <i class="fas fa-users"></i> Employees / Faculty
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
    <!-- Employees / Faculty Tab - now default active -->
    <div class="tab-pane fade show active" id="employees" role="tabpanel" aria-labelledby="employees-tab">
        <div class="row mb-4">
            <div class="col-12 d-flex gap-2 flex-wrap">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                    <i class="fas fa-plus"></i> Add Employee / Faculty
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
                            placeholder="Name, Employee ID, Department"
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
                            <i class="fas fa-file-pdf"></i> Print Employee List
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
                    Employee / Faculty List ({{ $employees->total() }} employees)
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
                                                @php $isEnrolled = ($employee->fingerprints_count ?? 0) > 0; @endphp
                                                <a href="{{ route('scan.employee.zk9500.enroll', ['employee_id' => $employee->employee_id_number, 'return_url' => request()->fullUrl()]) }}" class="action-btn" title="{{ $isEnrolled ? 'Change Fingerprint (already enrolled)' : 'Enroll Fingerprint' }}" style="background:{{ $isEnrolled ? '#ffc107;color:#111;border:1px solid #e0a800' : '#198754;color:#fff' }};display:inline-flex;align-items:center;justify-content:center;text-decoration:none;">
                                                    <i class="fas fa-fingerprint"></i>
                                                </a>
                                                <a href="/schedule?employee_id={{ $employee->id }}" class="action-btn" title="Schedule" style="background:#198754;color:#fff;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;">
                                                    <i class="fas fa-calendar-alt"></i>
                                                </a>
                                                <form method="POST" action="{{ route('users.destroy', $employee->id) }}" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="user_type" value="employee">
                                                    <button type="submit" class="action-btn delete" title="Delete" data-status="{{ $employee->status }}">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 30px; color: #999;">
                                            <i class="fas fa-inbox"></i> No employees found
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center" style="margin-top: 20px;">
                        <small class="text-muted">
                            @if($employees->total() > 0)
                                Showing {{ $employees->firstItem() }}–{{ $employees->lastItem() }} of {{ $employees->total() }}
                            @endif
                        </small>
                        <div>
                            {{ $employees->appends(request()->except('employee_page'))->links('pagination::bootstrap-5') }}
                        </div>
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
                    <button type="submit" class="action-btn delete" title="Delete">
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

<!-- Add Employee / Faculty Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Employee / Faculty</h5>
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
                        <label class="form-label">Department / Office</label>
                        <input type="text" name="department" class="form-control @error('department') is-invalid @enderror" placeholder="e.g., Administration, Faculty, HR">
                        @error('department') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                                    {{ $schedule->employee?->full_name ?? 'Schedule' }} - {{ $schedule->days_display }} | 
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
                    <button type="submit" class="btn btn-primary">Add Employee / Faculty</button>
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
                <h5 class="modal-title">Edit Employee / Faculty</h5>
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
                        <label class="form-label">Department / Office</label>
                        <input type="text" name="department" id="edit_emp_department" class="form-control">
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

<!-- Delete Confirmation Modal — replaces native confirm() -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="border-bottom:1px solid #e0e0e0;">
                <h5 class="modal-title" id="deleteConfirmTitle" style="color:#dc3545;"><i class="fas fa-exclamation-triangle" style="color:#dc3545;"></i> Delete Employee?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-3" style="font-size:52px;color:#dc3545;"><i class="fas fa-trash"></i></div>
                <p class="fs-5 mb-2" style="font-weight:600;color:#212529;" id="deleteConfirmHeading">This employee will be permanently deleted.</p>
                <p class="text-muted mb-1" id="deleteConfirmName" style="font-weight:500;"></p>
                <p class="text-muted small mb-0" id="deleteConfirmMessage">This action may also remove related attendance, schedule, fingerprint and credential records. This cannot be undone. Only Inactive employees can be deleted.</p>
            </div>
            <div class="modal-footer justify-content-center gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" style="font-weight:600;"><i class="fas fa-trash"></i> Delete Employee</button>
            </div>
        </div>
    </div>
</div>

<!-- Active Employee Delete Blocked Modal -->
<div class="modal fade" id="activeBlockedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="border-bottom:1px solid #e0e0e0;">
                <h5 class="modal-title" style="color:#dc3545;"><i class="fas fa-ban" style="color:#dc3545;"></i> Cannot Delete Active Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-3" style="font-size:52px;color:#dc3545;"><i class="fas fa-user-slash"></i></div>
                <p class="fs-5 mb-2" style="font-weight:600;color:#212529;">This employee is still Active.</p>
                <p class="text-muted mb-1" id="blockedEmployeeName" style="font-weight:500;"></p>
                <p class="text-muted small mb-0">Deletion is blocked while status is Active. Please change the employee status to <strong>Inactive</strong> first, then try again.</p>
            </div>
            <div class="modal-footer justify-content-center gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.edit-employee').forEach(btn => {
        btn.addEventListener('click', async function() {
            const employeeId = this.dataset.id;
            const response = await fetch(`/api/employees/${employeeId}`);
            const employee = await response.json();

            document.getElementById('edit_emp_first_name').value = employee.first_name;
            document.getElementById('edit_emp_last_name').value = employee.last_name;
            document.getElementById('edit_emp_department').value = employee.department || '';
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

    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab');

    if (tab) {
        const tabButton = document.getElementById(`${tab}-tab`);
        if (tabButton) {
            const bsTab = new bootstrap.Tab(tabButton);
            bsTab.show();
        }
    }

    // Delete confirmation modal — replaces native confirm() with Bootstrap modal
    // Wrapped in DOMContentLoaded so bootstrap.Modal is defined (app.blade.php loads bootstrap.bundle before scripts)
    let pendingDeleteForm = null;
    const deleteModalEl = document.getElementById('deleteConfirmModal');
    const deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;
    const blockedModalEl = document.getElementById('activeBlockedModal');
    const blockedModal = blockedModalEl ? new bootstrap.Modal(blockedModalEl) : null;
    const blockedNameEl = document.getElementById('blockedEmployeeName');
    const deleteTitle = document.getElementById('deleteConfirmTitle');
    const deleteHeading = document.getElementById('deleteConfirmHeading');
    const deleteNameEl = document.getElementById('deleteConfirmName');
    const deleteMsgEl = document.getElementById('deleteConfirmMessage');
    const confirmBtn = document.getElementById('confirmDeleteBtn');

    document.querySelectorAll('form[action*="/users/"] button.delete').forEach(btn => {
        const form = btn.closest('form');
        if (!form) return;
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const isVisitor = !!form.querySelector('input[name="user_type"][value="visitor"]');
            const status = btn.dataset.status || '';
            const isActive = !isVisitor && status === 'active';
            const row = form.closest('tr');
            const name = row ? (row.querySelector('td strong')?.textContent?.trim() || '') : '';
            if (isActive) {
                if (blockedNameEl) blockedNameEl.textContent = name ? '"' + name + '"' : '';
                if (blockedModal) blockedModal.show();
                else alert('Cannot delete an active employee. Set status to inactive first.');
                return;
            }
            pendingDeleteForm = form;
            if (deleteTitle) {
                deleteTitle.innerHTML = isVisitor
                    ? '<i class="fas fa-exclamation-triangle" style="color:#dc3545;"></i> Delete Visitor?'
                    : '<i class="fas fa-exclamation-triangle" style="color:#dc3545;"></i> Delete Employee?';
            }
            if (deleteHeading) {
                deleteHeading.textContent = isVisitor
                    ? 'This visitor will be permanently deleted.'
                    : 'This employee will be permanently deleted.';
            }
            if (deleteNameEl) {
                deleteNameEl.textContent = name ? '"' + name + '"' : '';
            }
            if (deleteMsgEl) {
                deleteMsgEl.textContent = isVisitor
                    ? 'This action cannot be undone.'
                    : 'This action may also remove related attendance, schedule, fingerprint and credential records. This cannot be undone. Only Inactive employees can be deleted.';
            }
            if (confirmBtn) {
                confirmBtn.innerHTML = isVisitor
                    ? '<i class="fas fa-trash"></i> Delete Visitor'
                    : '<i class="fas fa-trash"></i> Delete Employee';
            }
            if (deleteModal) {
                deleteModal.show();
            } else {
                if (confirm(isVisitor ? 'Are you sure you want to delete this visitor?' : 'Delete this employee? This action may also remove related records.')) {
                    pendingDeleteForm.submit();
                }
            }
        });
    });

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            if (deleteModal) deleteModal.hide();
            setTimeout(function() {
                if (pendingDeleteForm) pendingDeleteForm.submit();
            }, 300);
        });
    }

    if (deleteModalEl) {
        deleteModalEl.addEventListener('hidden.bs.modal', function() {
            pendingDeleteForm = null;
        });
    }
});
</script>
@endsection
