@extends('layouts.app')

@section('title', 'Student Attendance Calendar - ' . $student->first_name . ' ' . $student->last_name)

@section('content')
<div class="page-header">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1>{{ $student->first_name }} {{ $student->last_name }}</h1>
            <p>ID: {{ $student->student_id_number }} | Course: {{ $student->course ?? 'N/A' }}</p>
        </div>
        <a href="{{ route('attendance.student') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Students
        </a>
    </div>
</div>

<!-- Attendance Summary Cards -->
<div class="row mb-4">
    @php
        $present = $attendances->where('status', 'present')->count();
        $late = $attendances->where('status', 'late')->count();
        $absent = $attendances->where('status', 'absent')->count();
        $total = $attendances->count();
    @endphp
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-success">{{ $present }}</h3>
                <p class="text-muted mb-0">Present</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-warning">{{ $late }}</h3>
                <p class="text-muted mb-0">Late</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-danger">{{ $absent }}</h3>
                <p class="text-muted mb-0">Absent</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3>{{ $total }}</h3>
                <p class="text-muted mb-0">Total Records</p>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Records Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-history"></i> Attendance History
            </div>
            <div class="card-body">
                @if($attendances->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($attendances as $record)
                                    <tr>
                                        <td><strong>{{ $record->date->format('M d, Y') }}</strong></td>
                                        <td>{{ $record->date->format('l') }}</td>
                                        <td>
                                            @if($record->time_in)
                                                <span class="badge bg-primary">{{ $record->time_in }}</span>
                                            @else
                                                <span style="color: #999;">--</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($record->time_out)
                                                <span class="badge bg-primary">{{ $record->time_out }}</span>
                                            @else
                                                <span style="color: #999;">--</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($record->status === 'present')
                                                <span class="badge bg-success">On Time</span>
                                            @elseif($record->status === 'late')
                                                <span class="badge bg-warning text-dark">Late</span>
                                            @elseif($record->status === 'absent')
                                                <span class="badge bg-danger">Absent</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($record->status) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div style="padding: 60px; text-align: center; color: #999;">
                        <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 20px; display: block;"></i>
                        <h5>No attendance records found</h5>
                        <p>This student has no attendance records yet</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Calendar View by Month -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-calendar-alt"></i> Attendance by Month
            </div>
            <div class="card-body">
                @forelse($attendancesByMonth as $month => $records)
                    <div class="mb-4">
                        <h5 style="color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px;">
                            {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}
                        </h5>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(60px, 1fr)); gap: 10px; margin-top: 15px;">
                            @php
                                $monthStart = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
                                $monthEnd = \Carbon\Carbon::createFromFormat('Y-m', $month)->endOfMonth();
                                $daysInMonth = $monthEnd->day;
                            @endphp
                            @for ($day = 1; $day <= $daysInMonth; $day++)
                                @php
                                    $date = \Carbon\Carbon::create($monthStart->year, $monthStart->month, $day);
                                    $record = $records->first(fn($r) => $r->date->toDateString() === $date->toDateString());
                                @endphp
                                <div style="text-align: center; padding: 10px; border-radius: 5px; 
                                    @if($record)
                                        @if($record->status === 'present')
                                            background-color: #d4edda; border: 1px solid #28a745;
                                        @elseif($record->status === 'late')
                                            background-color: #fff3cd; border: 1px solid #ffc107;
                                        @else
                                            background-color: #f8d7da; border: 1px solid #dc3545;
                                        @endif
                                    @else
                                        background-color: #f0f0f0; border: 1px solid #ddd;
                                    @endif
                                    ">
                                    <div style="font-weight: bold; font-size: 14px;">{{ $day }}</div>
                                    @if($record)
                                        <div style="font-size: 10px; color: #666; margin-top: 3px;">
                                            @if($record->status === 'present')
                                                <span style="color: #28a745;">✓</span>
                                            @elseif($record->status === 'late')
                                                <span style="color: #ffc107;">⚠</span>
                                            @else
                                                <span style="color: #dc3545;">✗</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endfor
                        </div>
                    </div>
                @empty
                    <p style="color: #999; text-align: center; padding: 20px;">No attendance records available</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<style>
    .table-responsive {
        overflow-x: auto;
    }
    
    .badge {
        padding: 5px 10px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .card {
        border: none;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }
    
    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        padding: 15px;
        font-weight: 500;
    }
</style>
@endsection
