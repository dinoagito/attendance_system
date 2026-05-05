@extends('layouts.app')

@section('title', 'Employee Attendance')

@section('content')
<div class="page-header">
    <h1>Employee Attendance</h1>
    <p>View employee attendance history</p>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-filter"></i> Filters
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('attendance.employee') }}" id="filterForm">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Department</label>
                            <select name="department" class="form-control">
                                <option value="all">All Departments</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department }}" {{ ($filters['department'] ?? '') === $department ? 'selected' : '' }}>
                                        {{ $department }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <a href="{{ route('attendance.employee') }}" class="btn btn-secondary" style="width: 100%; color: white;">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Employees Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-users"></i> Employees ({{ $employees->total() }} total)
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name / ID</th>
                                <th>Department</th>
                                <th>Email</th>
                                <th>Total Records</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employees as $employee)
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <img src="{{ $employee->photo_path ? asset('storage/' . $employee->photo_path) : 'https://via.placeholder.com/40' }}" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                            <div>
                                                <div style="font-weight: 500;">{{ $employee->first_name }} {{ $employee->last_name }}</div>
                                                <div style="font-size: 12px; color: #999;">{{ $employee->employee_id_number }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $employee->department ?? 'N/A' }}</td>
                                    <td>{{ $employee->email ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ $employee->attendances_count }} records</span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('attendance.employee.calendar', $employee->id) }}" class="btn btn-sm btn-primary" title="View Calendar">
                                                <i class="fas fa-calendar"></i>
                                            </a>
                                            <a href="{{ route('schedule.index', ['employee_id' => $employee->id]) }}" class="btn btn-sm btn-schedule" title="View Schedule">
                                                <i class="fas fa-clock"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-success" title="Add Attendance" 
                                                    onclick="openAddModal({{ $employee->id }}, '{{ $employee->full_name }}')">
                                                <i class="fas fa-plus"></i>
                                            </button>
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

                <!-- Pagination -->
                <nav style="margin-top: 20px;">
                    {{ $employees->links() }}
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Add Attendance Modal -->
<div class="modal fade" id="addAttendanceModal" tabindex="-1" aria-labelledby="addAttendanceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAttendanceModalLabel">Add Attendance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addAttendanceForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p><strong>Employee:</strong> <span id="modalEmployeeName"></span></p>
                    
                    <div class="mb-3">
                        <label for="attendanceDate" class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="attendanceDate" name="date" required value="{{ date('Y-m-d') }}">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="timeIn" class="form-label">Time In</label>
                            <input type="time" class="form-control" id="timeIn" name="time_in" value="08:00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="timeOut" class="form-label">Time Out</label>
                            <input type="time" class="form-control" id="timeOut" name="time_out" value="17:00">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="Present">Present</option>
                            <option value="Late">Late</option>
                            <option value="Absent">Absent</option>
                            <option value="Undertime">Undertime</option>
                            <option value="Leave">Leave</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Attendance
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" style="position: fixed; top: 80px; right: 20px; z-index: 9999; max-width: 400px;">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" style="position: fixed; top: 80px; right: 20px; z-index: 9999; max-width: 400px;">
    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@endsection

@section('scripts')
<script>
function openAddModal(employeeId, employeeName) {
    document.getElementById('modalEmployeeName').textContent = employeeName;
    document.getElementById('addAttendanceForm').action = '/attendance/employee/' + employeeId + '/add';
    
    // Reset form
    document.getElementById('attendanceDate').value = '{{ date("Y-m-d") }}';
    document.getElementById('timeIn').value = '08:00';
    document.getElementById('timeOut').value = '17:00';
    document.getElementById('status').value = 'Present';
    
    // Show modal
    new bootstrap.Modal(document.getElementById('addAttendanceModal')).show();
}

// Auto-dismiss alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        document.querySelectorAll('.alert').forEach(function(alert) {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 150);
        });
    }, 5000);
});
</script>
@endsection
