@extends('layouts.app')

@section('title', 'Employee Attendance Calendar - ' . $employee->first_name . ' ' . $employee->last_name)

@section('content')
<div class="page-header">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1>{{ $employee->first_name }} {{ $employee->last_name }}</h1>
            <p>ID: {{ $employee->employee_id_number }} | Department: {{ $employee->department ?? 'N/A' }}</p>
        </div>
        <a href="{{ route('attendance.employee') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Employees
        </a>
    </div>
</div>

<!-- Attendance Summary Cards -->
<div class="row mb-4">
    @php
        $present = $attendances->where('status', 'Present')->count();
        $late = $attendances->where('status', 'Late')->count();
        $absent = $attendances->where('status', 'Absent')->count();
        $leave = $attendances->where('status', 'Leave')->count();
        $total = $attendances->count();
    @endphp
    <div class="col-md-3 col-6 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-success">{{ $present }}</h3>
                <p class="text-muted mb-0">Present</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-warning">{{ $late }}</h3>
                <p class="text-muted mb-0">Late</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-danger">{{ $absent }}</h3>
                <p class="text-muted mb-0">Absent</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-info">{{ $leave }}</h3>
                <p class="text-muted mb-0">Leave</p>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Records Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-history"></i> Attendance History ({{ $total }} records)
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
                                @php
                                    // Group records by date and select the record with the maximum (latest) time_out
                                    $groupedByDate = $attendances->groupBy(function($record) {
                                        return $record->date->toDateString();
                                    })->map(function($group) {
                                        // Find the record with the latest time value across all time_out columns
                                        $best = null;
                                        $latestTs = 0;
                                        $latestTimeOut = null;
                                        foreach ($group as $r) {
                                            // Check all time_out columns (time_out, time_out_2, time_out_3, time_out_4)
                                            $timeOutValues = [
                                                $r->time_out ?? null,
                                                $r->time_out_2 ?? null,
                                                $r->time_out_3 ?? null,
                                                $r->time_out_4 ?? null,
                                            ];
                                            // Filter out null values and find the maximum timestamp
                                            foreach ($timeOutValues as $timeVal) {
                                                if (!is_null($timeVal)) {
                                                    try {
                                                        $ts = \Carbon\Carbon::parse($timeVal)->timestamp;
                                                        if ($ts >= $latestTs) {
                                                            $latestTs = $ts;
                                                            $best = $r;
                                                            $latestTimeOut = $timeVal;
                                                        }
                                                    } catch (\Exception $e) {
                                                        // Skip invalid timestamps
                                                    }
                                                }
                                            }
                                        }
                                        // If no time_out found, fall back to time_in columns
                                        if ($best === null) {
                                            foreach ($group as $r) {
                                                $timeInValues = [$r->time_in ?? null, $r->time_in_2 ?? null, $r->time_in_3 ?? null, $r->time_in_4 ?? null];
                                                foreach ($timeInValues as $timeVal) {
                                                    if (!is_null($timeVal)) {
                                                        try {
                                                            $ts = \Carbon\Carbon::parse($timeVal)->timestamp;
                                                            if ($ts >= $latestTs) {
                                                                $latestTs = $ts;
                                                                $best = $r;
                                                            }
                                                        } catch (\Exception $e) {}
                                                    }
                                                }
                                            }
                                        }
                                        // Last resort: return first record if still null
                                        $result = $best ?? $group->first();
                                        $result->_latestTimeOut = $latestTimeOut;
                                        return $result;
                                    })->sortByDesc(function($record) {
                                        return $record->date;
                                    });
                                @endphp
                                @foreach($groupedByDate as $record)
                                    <tr>
                                        <td><strong>{{ $record->date->format('M d, Y') }}</strong></td>
                                        <td>{{ $record->date->format('l') }}</td>
                                        <td>
                                            @if($record->time_in)
                                                <span class="badge bg-primary">{{ date('h:i A', strtotime($record->time_in)) }}</span>
                                            @else
                                                <span style="color: #999;">--</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($record->_latestTimeOut)
                                                <span class="badge bg-primary">{{ date('h:i A', strtotime($record->_latestTimeOut)) }}</span>
                                            @elseif($record->time_out)
                                                <span class="badge bg-primary">{{ date('h:i A', strtotime($record->time_out)) }}</span>
                                            @else
                                                <span style="color: #999;">--</span>
                                            @endif
                                        </td>
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
                    <div style="padding: 60px; text-align: center; color: #999;">
                        <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 20px; display: block;"></i>
                        <h5>No attendance records found</h5>
                        <p>This employee has no attendance records yet</p>
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
                                    // Get all records for this date and pick the maximum (latest) time_out
                                    $daily = $records->filter(fn($r) => $r->date->toDateString() === $date->toDateString());
                                    if ($daily->isNotEmpty()) {
                                        $record = null;
                                        $latestTs = 0;
                                        $latestTimeOut = null;
                                        // Find the record with the latest time value across all time_out columns
                                        foreach ($daily as $r) {
                                            $timeOutValues = [$r->time_out ?? null, $r->time_out_2 ?? null, $r->time_out_3 ?? null, $r->time_out_4 ?? null];
                                            foreach ($timeOutValues as $timeVal) {
                                                if (!is_null($timeVal)) {
                                                    try {
                                                        $ts = \Carbon\Carbon::parse($timeVal)->timestamp;
                                                        if ($ts >= $latestTs) {
                                                            $latestTs = $ts;
                                                            $record = $r;
                                                            $latestTimeOut = $timeVal;
                                                        }
                                                    } catch (\Exception $e) {}
                                                }
                                            }
                                        }
                                        // If no time_out found, fall back to time_in columns
                                        if ($record === null) {
                                            foreach ($daily as $r) {
                                                $timeInValues = [$r->time_in ?? null, $r->time_in_2 ?? null, $r->time_in_3 ?? null, $r->time_in_4 ?? null];
                                                foreach ($timeInValues as $timeVal) {
                                                    if (!is_null($timeVal)) {
                                                        try {
                                                            $ts = \Carbon\Carbon::parse($timeVal)->timestamp;
                                                            if ($ts >= $latestTs) {
                                                                $latestTs = $ts;
                                                                $record = $r;
                                                            }
                                                        } catch (\Exception $e) {}
                                                    }
                                                }
                                            }
                                        }
                                        // Last resort: use first record if still null
                                        if ($record === null) {
                                            $record = $daily->first();
                                        }
                                        $record->_latestTimeOut = $latestTimeOut;
                                    } else {
                                        $record = null;
                                    }
                                @endphp
                                <div style="text-align: center; padding: 10px; border-radius: 5px; 
                                    @if($record)
                                        @if($record->status === 'Present')
                                            background-color: #d4edda; border: 1px solid #28a745;
                                        @elseif($record->status === 'Late')
                                            background-color: #fff3cd; border: 1px solid #ffc107;
                                        @elseif($record->status === 'Leave')
                                            background-color: #cce5ff; border: 1px solid #004085;
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
                                            @if($record->status === 'Present')
                                                <span style="color: #28a745;">✓</span>
                                            @elseif($record->status === 'Late')
                                                <span style="color: #ffc107;">⚠</span>
                                            @elseif($record->status === 'Leave')
                                                <span style="color: #004085;">L</span>
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
