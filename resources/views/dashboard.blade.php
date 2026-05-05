@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="page-header">
    <h1>Dashboard</h1>
    <p>Welcome back! Here's your attendance overview for {{ now()->format('F d, Y') }}.</p>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stat-card blue">
            <div class="stat-card-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="stat-card-value">{{ number_format($totalStudents) }}</div>
            <div class="stat-card-label">Total Students</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stat-card green">
            <div class="stat-card-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-card-value">{{ number_format($totalEmployees) }}</div>
            <div class="stat-card-label">Employees</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stat-card orange">
            <div class="stat-card-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-card-value">{{ number_format($totalPresentToday) }}</div>
            <div class="stat-card-label">Present Today</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="stat-card red">
            <div class="stat-card-icon">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="stat-card-value">{{ number_format($visitorsToday) }}</div>
            <div class="stat-card-label">Visitors Today</div>
        </div>
    </div>
</div>

<!-- Charts and Tables Row -->
<div class="row">
    <!-- Attendance Chart -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-bar"></i> Weekly Attendance Overview
            </div>
            <div class="card-body">
                <canvas id="attendanceChart" style="height: 300px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Attendance Summary -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-calendar-check"></i> Attendance Summary
            </div>
            <div class="card-body">
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-size: 14px;">Students Present</span>
                        <strong style="color: #27ae60;">{{ number_format($studentsPresent) }} ({{ $studentPresentPercent }}%)</strong>
                    </div>
                    <div style="height: 6px; background-color: #e0e0e0; border-radius: 3px; overflow: hidden;">
                        <div style="height: 100%; width: {{ $studentPresentPercent }}%; background-color: #27ae60;"></div>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-size: 14px;">Students Late</span>
                        <strong style="color: #f39c12;">{{ number_format($studentsLate) }} ({{ $studentLatePercent }}%)</strong>
                    </div>
                    <div style="height: 6px; background-color: #e0e0e0; border-radius: 3px; overflow: hidden;">
                        <div style="height: 100%; width: {{ $studentLatePercent }}%; background-color: #f39c12;"></div>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-size: 14px;">Students Absent</span>
                        <strong style="color: #e74c3c;">{{ number_format($studentsAbsent) }} ({{ $studentAbsentPercent }}%)</strong>
                    </div>
                    <div style="height: 6px; background-color: #e0e0e0; border-radius: 3px; overflow: hidden;">
                        <div style="height: 100%; width: {{ $studentAbsentPercent }}%; background-color: #e74c3c;"></div>
                    </div>
                </div>

                <div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-size: 14px;">Employees Present</span>
                        <strong style="color: #27ae60;">{{ number_format($employeesPresent) }} ({{ $employeePresentPercent }}%)</strong>
                    </div>
                    <div style="height: 6px; background-color: #e0e0e0; border-radius: 3px; overflow: hidden;">
                        <div style="height: 100%; width: {{ $employeePresentPercent }}%; background-color: #27ae60;"></div>
                    </div>
                </div>

                <div style="margin-top: 20px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-size: 14px;">Employees Late</span>
                        <strong style="color: #f39c12;">{{ number_format($employeesLate) }} ({{ $employeeLatePercent }}%)</strong>
                    </div>
                    <div style="height: 6px; background-color: #e0e0e0; border-radius: 3px; overflow: hidden;">
                        <div style="height: 100%; width: {{ $employeeLatePercent }}%; background-color: #f39c12;"></div>
                    </div>
                </div>

                <div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-size: 14px;">Employees Absent</span>
                        <strong style="color: #e74c3c;">{{ number_format($employeesAbsent) }} ({{ $employeeAbsentPercent }}%)</strong>
                    </div>
                    <div style="height: 6px; background-color: #e0e0e0; border-radius: 3px; overflow: hidden;">
                        <div style="height: 100%; width: {{ $employeeAbsentPercent }}%; background-color: #e74c3c;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Attendance -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-history"></i> Recent Attendance Records
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>ID</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentAttendance as $record)
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        @if($record['photo'])
                                            <img src="{{ asset('storage/' . $record['photo']) }}" alt="Profile" class="profile-pic" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                        @else
                                            <div style="width: 40px; height: 40px; border-radius: 50%; background-color: #e0e0e0; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-user" style="color: #999;"></i>
                                            </div>
                                        @endif
                                        <span>{{ $record['name'] }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if($record['type'] === 'Student')
                                        <span style="font-size: 12px; background-color: #e3f2fd; color: #1976d2; padding: 4px 8px; border-radius: 3px;">Student</span>
                                    @else
                                        <span style="font-size: 12px; background-color: #f3e5f5; color: #7b1fa2; padding: 4px 8px; border-radius: 3px;">Employee</span>
                                    @endif
                                </td>
                                <td>{{ $record['id'] }}</td>
                                <td>{{ $record['time_in'] }}</td>
                                <td>{{ $record['time_out'] }}</td>
                                <td>{!! $record['status_badge'] !!}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: #999;">
                                    <i class="fas fa-inbox" style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                                    No attendance records for today yet.
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
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    const weeklyData = @json($weeklyData);
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: weeklyData.map(d => d.day + '\n' + d.date),
            datasets: [
                {
                    label: 'Students',
                    data: weeklyData.map(d => d.students),
                    backgroundColor: '#3498db',
                    borderRadius: 4,
                },
                {
                    label: 'Employees',
                    data: weeklyData.map(d => d.employees),
                    backgroundColor: '#27ae60',
                    borderRadius: 4,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
</script>
@endsection
