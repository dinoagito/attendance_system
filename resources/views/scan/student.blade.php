@extends('layouts.app')

@section('title', 'RFID Student Scan')

@section('content')
<div class="page-header">
    <h1>RFID Student Scan</h1>
    <p>Scan student RFID card or enter ID to record attendance</p>
</div>

<div class="row">
    <!-- Scan Interface -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-qrcode"></i> Scan RFID Card
            </div>
            <div class="card-body" style="text-align: center; padding: 40px 20px;">
                <div style="margin-bottom: 30px;">
                    <i class="fas fa-id-card" id="scanIcon" style="font-size: 80px; color: #3498db;"></i>
                </div>

                <div id="scanStatus" style="background-color: #ecf0f1; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h5 style="margin-bottom: 15px; color: #2c3e50;">Ready to scan</h5>
                    <p style="color: #7f8c8d; margin-bottom: 0;">
                        <i class="fas fa-circle-notch" style="animation: spin 1s linear infinite;"></i>
                        Enter student ID or scan RFID card
                    </p>
                </div>

                <form id="scanForm">
                    @csrf
                    <input type="text" id="studentIdInput" name="student_id" class="form-control" 
                           placeholder="Enter or scan student ID (e.g., STU-001)" 
                           style="margin-bottom: 15px; text-align: center; font-size: 18px;" 
                           autofocus autocomplete="off">
                    
                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-bottom: 10px;">
                        <i class="fas fa-check"></i> Record Attendance
                    </button>
                </form>

                <button type="button" id="clearBtn" class="btn btn-secondary" style="width: 100%;">
                    <i class="fas fa-redo-alt"></i> Clear & Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Scan Result -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-check-circle"></i> Scan Result
            </div>
            <div class="card-body" id="resultCard" style="text-align: center; padding: 40px 20px;">
                <div id="noResult" style="color: #999; padding: 50px;">
                    <i class="fas fa-user-clock" style="font-size: 60px; margin-bottom: 15px;"></i>
                    <p>Scan result will appear here</p>
                </div>
                
                <div id="resultContent" style="display: none;">
                    <div id="resultStatus" style="padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 5px solid;">
                        <h5 id="resultTitle" style="margin-bottom: 10px;">
                            <i class="fas fa-check-circle"></i> <span></span>
                        </h5>
                        <p id="resultMessage" style="margin-bottom: 0;"></p>
                    </div>

                    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <img id="studentPhoto" src="" alt="Profile" class="profile-pic-large" style="margin-bottom: 15px; width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
                        <h5 id="studentName" style="margin-bottom: 5px; color: #2c3e50;"></h5>
                        <p id="studentIdDisplay" style="color: #7f8c8d; margin-bottom: 15px;"></p>

                        <div style="text-align: left; background-color: white; padding: 15px; border-radius: 5px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px solid #e0e0e0; padding-bottom: 10px;">
                                <span style="color: #7f8c8d;">Course:</span>
                                <strong id="studentCourse"></strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px solid #e0e0e0; padding-bottom: 10px;">
                                <span style="color: #7f8c8d;">Time In:</span>
                                <strong id="timeIn"></strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px solid #e0e0e0; padding-bottom: 10px;">
                                <span style="color: #7f8c8d;">Time Out:</span>
                                <strong id="timeOut"></strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding-bottom: 10px;">
                                <span style="color: #7f8c8d;">Status:</span>
                                <strong id="attendanceStatus"></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Scans -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-history"></i> Recent Scans (Today)
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>ID</th>
                                <th>Course</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="recentScansBody">
                            @forelse($recentScans as $scan)
                            <tr>
                                <td>{{ $scan->student->full_name ?? 'Unknown' }}</td>
                                <td>{{ $scan->student->student_id_number ?? '-' }}</td>
                                <td>{{ $scan->student->course ?? '-' }}</td>
                                <td>{{ $scan->time_in ? date('h:i A', strtotime($scan->time_in)) : '--' }}</td>
                                <td>{{ $scan->time_out ? date('h:i A', strtotime($scan->time_out)) : '--' }}</td>
                                <td>
                                    @if($scan->status === 'present')
                                        <span class="badge-status badge-on-time">On Time</span>
                                    @elseif($scan->status === 'late')
                                        <span class="badge-status badge-late">Late</span>
                                    @elseif($scan->status === 'absent')
                                        <span class="badge-status badge-absent">Absent</span>
                                    @else
                                        <span class="badge-status">{{ ucfirst($scan->status) }}</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr id="noScansRow">
                                <td colspan="6" style="text-align: center; padding: 30px; color: #999;">
                                    <i class="fas fa-inbox"></i> No scans recorded today
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const scanForm = document.getElementById('scanForm');
    const studentIdInput = document.getElementById('studentIdInput');
    const clearBtn = document.getElementById('clearBtn');
    const scanIcon = document.getElementById('scanIcon');
    const scanStatus = document.getElementById('scanStatus');
    const noResult = document.getElementById('noResult');
    const resultContent = document.getElementById('resultContent');
    const recentScansBody = document.getElementById('recentScansBody');

    // Focus on input
    studentIdInput.focus();

    // Form submission
    scanForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const studentId = studentIdInput.value.trim();
        if (!studentId) {
            showError('Please enter a student ID');
            return;
        }

        // Show loading state
        scanIcon.style.color = '#f39c12';
        scanStatus.innerHTML = `
            <h5 style="margin-bottom: 15px; color: #f39c12;">Processing...</h5>
            <p style="color: #7f8c8d; margin-bottom: 0;">
                <i class="fas fa-spinner" style="animation: spin 1s linear infinite;"></i>
                Looking up student...
            </p>
        `;

        try {
            const response = await fetch('{{ route("scan.student.process") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ student_id: studentId }),
            });

            const data = await response.json();

            if (data.success) {
                showSuccess(data);
                addToRecentScans(data);
            } else {
                showError(data.message, data.student, data.attendance);
            }
        } catch (error) {
            showError('Network error. Please try again.');
            console.error(error);
        }

        // Clear input for next scan
        studentIdInput.value = '';
        studentIdInput.focus();
    });

    // Clear button
    clearBtn.addEventListener('click', function() {
        resetForm();
    });

    function showSuccess(data) {
        scanIcon.style.color = '#27ae60';
        const actionText = data.action === 'time_in' ? 'Time In Recorded' : 'Time Out Recorded';
        scanStatus.innerHTML = `
            <h5 style="margin-bottom: 15px; color: #27ae60;"><i class="fas fa-check-circle"></i> ${actionText}</h5>
            <p style="color: #155724; margin-bottom: 0;">${data.message}</p>
        `;

        noResult.style.display = 'none';
        resultContent.style.display = 'block';

        const resultStatus = document.getElementById('resultStatus');
        resultStatus.style.backgroundColor = '#d4edda';
        resultStatus.style.borderColor = '#28a745';
        document.getElementById('resultTitle').innerHTML = `<i class="fas fa-check-circle"></i> ${actionText}`;
        document.getElementById('resultTitle').style.color = '#155724';
        document.getElementById('resultMessage').textContent = data.message;
        document.getElementById('resultMessage').style.color = '#155724';

        // Student info
        const photoUrl = data.student.photo ? '/storage/' + data.student.photo : 'https://via.placeholder.com/100';
        document.getElementById('studentPhoto').src = photoUrl;
        document.getElementById('studentName').textContent = data.student.name;
        document.getElementById('studentIdDisplay').textContent = data.student.student_id_number;
        document.getElementById('studentCourse').textContent = data.student.course || 'N/A';
        document.getElementById('timeIn').textContent = data.attendance.time_in || '--';
        document.getElementById('timeOut').textContent = data.attendance.time_out || '--';
        
        const statusEl = document.getElementById('attendanceStatus');
        statusEl.textContent = data.attendance.status;
        statusEl.style.color = data.attendance.status === 'Late' ? '#f39c12' : '#27ae60';
    }

    function showError(message, student = null, attendance = null) {
        scanIcon.style.color = '#e74c3c';
        scanStatus.innerHTML = `
            <h5 style="margin-bottom: 15px; color: #e74c3c;"><i class="fas fa-times-circle"></i> Error</h5>
            <p style="color: #721c24; margin-bottom: 0;">${message}</p>
        `;

        if (student) {
            noResult.style.display = 'none';
            resultContent.style.display = 'block';

            const resultStatus = document.getElementById('resultStatus');
            resultStatus.style.backgroundColor = '#f8d7da';
            resultStatus.style.borderColor = '#dc3545';
            document.getElementById('resultTitle').innerHTML = `<i class="fas fa-info-circle"></i> Already Recorded`;
            document.getElementById('resultTitle').style.color = '#721c24';
            document.getElementById('resultMessage').textContent = message;
            document.getElementById('resultMessage').style.color = '#721c24';

            document.getElementById('studentPhoto').src = 'https://via.placeholder.com/100';
            document.getElementById('studentName').textContent = student.name;
            document.getElementById('studentIdDisplay').textContent = student.student_id_number;
            document.getElementById('studentCourse').textContent = student.course || 'N/A';
            
            if (attendance) {
                document.getElementById('timeIn').textContent = attendance.time_in || '--';
                document.getElementById('timeOut').textContent = attendance.time_out || '--';
                document.getElementById('attendanceStatus').textContent = attendance.status;
            }
        }

        // Reset after 3 seconds
        setTimeout(resetForm, 3000);
    }

    function addToRecentScans(data) {
        // Remove "no scans" row if present
        const noScansRow = document.getElementById('noScansRow');
        if (noScansRow) noScansRow.remove();

        const statusBadge = data.attendance.status === 'Late' 
            ? '<span class="badge-status badge-late">Late</span>'
            : '<span class="badge-status badge-on-time">On Time</span>';

        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>${data.student.name}</td>
            <td>${data.student.student_id_number}</td>
            <td>${data.student.course || '-'}</td>
            <td>${data.attendance.time_in || '--'}</td>
            <td>${data.attendance.time_out || '--'}</td>
            <td>${statusBadge}</td>
        `;
        newRow.style.backgroundColor = '#d4edda';
        
        // Insert at top
        recentScansBody.insertBefore(newRow, recentScansBody.firstChild);

        // Fade out highlight
        setTimeout(() => {
            newRow.style.transition = 'background-color 1s ease';
            newRow.style.backgroundColor = '';
        }, 500);
    }

    function resetForm() {
        scanIcon.style.color = '#3498db';
        scanStatus.innerHTML = `
            <h5 style="margin-bottom: 15px; color: #2c3e50;">Ready to scan</h5>
            <p style="color: #7f8c8d; margin-bottom: 0;">
                <i class="fas fa-circle-notch" style="animation: spin 1s linear infinite;"></i>
                Enter student ID or scan RFID card
            </p>
        `;
        noResult.style.display = 'block';
        resultContent.style.display = 'none';
        studentIdInput.value = '';
        studentIdInput.focus();
    }
});
</script>
@endsection
