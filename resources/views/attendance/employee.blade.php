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
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-filter"></i> Filters</span>
                @if($employees)
                    <a href="{{ route('attendance.employee.print', array_filter($filters)) }}" target="_blank"
                       class="btn btn-sm btn-outline-success">
                        <i class="fas fa-print"></i> Print Employee List
                    </a>
                @endif
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('attendance.employee') }}" id="filterForm">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-2 col-md-3 col-sm-6 col-12">
                            <label class="form-label">Department</label>
                            <select name="department" class="form-control w-100">
                                <option value="all">All Departments</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department }}" {{ ($filters['department'] ?? '') === $department ? 'selected' : '' }}>
                                        {{ $department }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-3 col-sm-6 col-12">
                            <label class="form-label text-nowrap">Employee Name / Employee No.</label>
                            <input type="text" name="employee" class="form-control w-100"
                                   value="{{ $filters['employee'] ?? '' }}" placeholder="Type name or employee no.">
                        </div>
                        <div class="col-lg-2 col-md-3 col-sm-6 col-12">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control w-100" value="{{ $filters['date_from'] ?? '' }}">
                        </div>
                        <div class="col-lg-2 col-md-3 col-sm-6 col-12">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control w-100" value="{{ $filters['date_to'] ?? '' }}">
                        </div>
                        <div class="col-lg-1 col-md-2 col-sm-4 col-6">
                            <button type="submit" name="action" value="search" class="btn btn-primary w-100 text-nowrap">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                        <div class="col-lg-2 col-md-3 col-sm-8 col-6">
                            <button type="submit" name="action" value="all" class="btn btn-info w-100 text-nowrap" style="color: white;">
                                <i class="fas fa-users"></i> All Employees
                            </button>
                        </div>
                        <div class="col-lg-1 col-md-2 col-sm-12 col-12">
                            <a href="{{ route('attendance.employee') }}" class="btn btn-secondary w-100 text-nowrap" style="color: white;">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Report -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-users"></i>
                @if($employees)
                    Employees ({{ $employees->total() }} total)
                @else
                    Attendance Report
                @endif
            </div>
            <div class="card-body">
                @if(is_null($employees))
                    <div style="padding: 60px; text-align: center; color: #999;">
                        <i class="fas fa-search" style="font-size: 48px; margin-bottom: 20px; display: block;"></i>
                        <h5>Select a filter and click Search to view attendance records.</h5>
                    </div>
                @else
                @forelse($employees as $employee)
                    @php
                        $records = $employee->attendances;
                        // Determine how many time-in/time-out column pairs are needed:
                        // use the maximum number of populated pairs found across the employee's dates.
                        $maxSlots = 0;
                        foreach ($records as $rec) {
                            $pairs = 0;
                            if ($rec->time_in && $rec->time_out) $pairs++;
                            if ($rec->time_in_2 && $rec->time_out_2) $pairs++;
                            if ($rec->time_in_3 && $rec->time_out_3) $pairs++;
                            if ($rec->time_in_4 && $rec->time_out_4) $pairs++;
                            $maxSlots = max($maxSlots, $pairs);
                        }
                    @endphp

                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <h5 class="mb-0">
                                    <i class="fas fa-user" style="color: #999;"></i>
                                    {{ $employee->full_name }}
                                </h5>
                                <small class="text-muted">
                                    Employee No: {{ $employee->employee_id_number }}
                                    @if($employee->department)
                                        &nbsp;|&nbsp; <span class="badge bg-secondary">{{ $employee->department }}</span>
                                    @endif
                                </small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-info">{{ $records->count() }} record(s)</span>
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
                            </div>
                        </div>

                        @if($records->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            @for($i = 1; $i <= $maxSlots; $i++)
                                                <th>Time In</th>
                                                <th>Time Out</th>
                                            @endfor
                                            <th>Total Hours</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($records as $record)
                                            @php
                                                $slots = [
                                                    1 => [$record->time_in, $record->time_out],
                                                    2 => [$record->time_in_2, $record->time_out_2],
                                                    3 => [$record->time_in_3, $record->time_out_3],
                                                    4 => [$record->time_in_4, $record->time_out_4],
                                                ];
                                                $totalMinutes = 0;
                                                foreach ($slots as $pair) {
                                                    if ($pair[0] && $pair[1]) {
                                                        $in = \Carbon\Carbon::parse($pair[0]);
                                                        $out = \Carbon\Carbon::parse($pair[1]);
                                                        if ($out->gt($in)) {
                                                            $totalMinutes += $in->diffInMinutes($out);
                                                        }
                                                    }
                                                }
                                                $totalHrs = intdiv($totalMinutes, 60);
                                                $totalMin = $totalMinutes % 60;
                                                $totalLabel = $totalMinutes > 0
                                                    ? $totalHrs . 'h ' . str_pad($totalMin, 2, '0', STR_PAD_LEFT) . 'm'
                                                    : '-';
                                            @endphp
                                            <tr>
                                                <td><strong>{{ $record->date->format('M d, Y') }}</strong><br>
                                                    <small class="text-muted">{{ $record->date->format('l') }}</small>
                                                </td>
                                                @for($i = 1; $i <= $maxSlots; $i++)
                                                    <td>
                                                        @if($slots[$i][0])
                                                            <span class="badge bg-primary">{{ date('h:i A', strtotime($slots[$i][0])) }}</span>
                                                        @else
                                                            <span style="color: #999;">--</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($slots[$i][1])
                                                            <span class="badge bg-primary">{{ date('h:i A', strtotime($slots[$i][1])) }}</span>
                                                        @else
                                                            <span style="color: #999;">--</span>
                                                        @endif
                                                    </td>
                                                @endfor
                                                <td><span class="badge bg-dark">{{ $totalLabel }}</span></td>
                                                <td>
                                                    @if($record->status === 'Present')
                                                        <span class="badge bg-success">Present</span>
                                                    @elseif($record->status === 'Late')
                                                        <span class="badge bg-warning text-dark">Late</span>
                                                    @elseif($record->status === 'Absent')
                                                        <span class="badge bg-danger">Absent</span>
                                                    @elseif($record->status === 'Leave')
                                                        <span class="badge bg-info">Leave</span>
                                                    @elseif($record->status === 'Undertime')
                                                        <span class="badge bg-secondary">Undertime</span>
                                                    @else
                                                        <span class="badge bg-secondary">{{ $record->status }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-inbox"></i> No attendance records in the selected date range.
                            </div>
                        @endif
                    </div>
                @empty
                    <div style="padding: 60px; text-align: center; color: #999;">
                        <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 20px; display: block;"></i>
                        <h5>No employees found</h5>
                        <p>No employees match the selected filters.</p>
                    </div>
                @endforelse

                <!-- Pagination (by employee group) -->
                <nav style="margin-top: 20px;">
                    {{ $employees->links() }}
                </nav>
                @endif
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
