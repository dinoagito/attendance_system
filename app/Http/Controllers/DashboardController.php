<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Visitor;
use App\Models\EmployeeAttendance;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        // Total counts - Employee/Faculty only (Student removed)
        $totalEmployees = Employee::count();

        // Today's visitors
        $visitorsToday = Visitor::whereDate('date', $today)->count();

        // Employee attendance today
        $employeeAttendanceToday = EmployeeAttendance::whereDate('date', $today);
        $employeesPresent = (clone $employeeAttendanceToday)->where('status', 'Present')->count();
        $employeesLate = (clone $employeeAttendanceToday)->where('status', 'Late')->count();
        $employeesAbsent = (clone $employeeAttendanceToday)->where('status', 'Absent')->count();
        $employeePresentPercent = $totalEmployees > 0 ? round(($employeesPresent / $totalEmployees) * 100) : 0;
        $employeeLatePercent = $totalEmployees > 0 ? round(($employeesLate / $totalEmployees) * 100) : 0;
        $employeeAbsentPercent = $totalEmployees > 0 ? round(($employeesAbsent / $totalEmployees) * 100) : 0;

        // Total present today (employees only)
        $totalPresentToday = $employeesPresent + $employeesLate;

        // Recent attendance records - employees only
        $recentAttendance = EmployeeAttendance::with('employee')
            ->whereDate('date', $today)
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($attendance) {
                return [
                    'name' => $attendance->employee->full_name ?? 'Unknown',
                    'type' => 'Employee',
                    'type_class' => 'employee',
                    'id' => $attendance->employee->employee_id_number ?? '-',
                    'time_in' => $attendance->time_in ? date('h:i A', strtotime($attendance->time_in)) : '--',
                    'time_out' => $attendance->time_out ? date('h:i A', strtotime($attendance->time_out)) : '--',
                    'status' => $attendance->status,
                    'status_badge' => '<span class="badge-status ' . $attendance->status_badge . '">' . $attendance->status . '</span>',
                    'photo' => $attendance->employee->photo_path ?? null,
                    'created_at' => $attendance->created_at,
                ];
            })
            ->sortByDesc('created_at')
            ->take(10)
            ->values();

        // Weekly attendance data for chart (last 7 days) - employees only
        $weeklyData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dayName = $date->format('D');
            
            $employeeCount = EmployeeAttendance::whereDate('date', $date)
                ->whereIn('status', ['Present', 'Late'])
                ->count();

            $weeklyData[] = [
                'day' => $dayName,
                'date' => $date->format('M d'),
                'employees' => $employeeCount,
            ];
        }

        return view('dashboard', compact(
            'totalEmployees',
            'visitorsToday',
            'totalPresentToday',
            'employeesPresent',
            'employeesLate',
            'employeesAbsent',
            'employeePresentPercent',
            'employeeLatePercent',
            'employeeAbsentPercent',
            'recentAttendance',
            'weeklyData'
        ));
    }
}
