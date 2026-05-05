@extends('layouts.app')

@section('title', 'Student Attendance')

@section('content')
<div class="page-header">
    <h1>Student Attendance</h1>
    <p>View student attendance history</p>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-filter"></i> Filters
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('attendance.student') }}" id="filterForm">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Course</label>
                            <select name="course" class="form-control">
                                <option value="all">All Courses</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course }}" {{ ($filters['course'] ?? '') === $course ? 'selected' : '' }}>
                                        {{ $course }}
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
                            <a href="{{ route('attendance.student') }}" class="btn btn-secondary" style="width: 100%; color: white;">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Students Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-users"></i> Students ({{ $students->total() }} total)
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name / ID</th>
                                <th>Course</th>
                                <th>Section</th>
                                <th>Total Records</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <img src="{{ $student->photo_path ? asset('storage/' . $student->photo_path) : 'https://via.placeholder.com/40' }}" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                            <div>
                                                <div style="font-weight: 500;">{{ $student->first_name }} {{ $student->last_name }}</div>
                                                <div style="font-size: 12px; color: #999;">{{ $student->student_id_number }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $student->course ?? 'N/A' }}</td>
                                    <td>{{ $student->section ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ $student->attendances_count }} records</span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('attendance.student.calendar', $student->id) }}" class="btn btn-sm btn-primary" title="View Calendar">
                                                <i class="fas fa-calendar"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-success" title="Add Attendance" 
                                                    onclick="openAddModal({{ $student->id }}, '{{ $student->full_name }}')">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 30px; color: #999;">
                                        <i class="fas fa-inbox"></i> No students found
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav style="margin-top: 20px;">
                    {{ $students->links() }}
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
                    <p><strong>Student:</strong> <span id="modalStudentName"></span></p>
                    
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
                            <input type="time" class="form-control" id="timeOut" name="time_out">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="present">Present (On Time)</option>
                            <option value="late">Late</option>
                            <option value="absent">Absent</option>
                            <option value="excused">Excused</option>
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
function openAddModal(studentId, studentName) {
    document.getElementById('modalStudentName').textContent = studentName;
    document.getElementById('addAttendanceForm').action = '/attendance/student/' + studentId + '/add';
    
    // Reset form
    document.getElementById('attendanceDate').value = '{{ date("Y-m-d") }}';
    document.getElementById('timeIn').value = '08:00';
    document.getElementById('timeOut').value = '';
    document.getElementById('status').value = 'present';
    
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
