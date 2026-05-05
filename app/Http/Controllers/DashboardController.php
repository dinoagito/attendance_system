<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Employee;
use App\Models\Visitor;
use App\Models\StudentAttendance;
use App\Models\EmployeeAttendance;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        // Total counts
        $totalStudents = Student::count();
        $totalEmployees = Employee::count();

        // Today's visitors
        $visitorsToday = Visitor::whereDate('date', $today)->count();

        // Student attendance today
        $studentAttendanceToday = StudentAttendance::whereDate('date', $today);
        $studentsPresent = (clone $studentAttendanceToday)->where('status', 'present')->count();
        $studentsLate = (clone $studentAttendanceToday)->where('status', 'late')->count();
        $studentsAbsent = (clone $studentAttendanceToday)->where('status', 'absent')->count();
        $totalStudentAttendanceToday = $studentsPresent + $studentsLate + $studentsAbsent;

        // Calculate percentages for students
        $studentPresentPercent = $totalStudents > 0 ? round(($studentsPresent / $totalStudents) * 100) : 0;
        $studentLatePercent = $totalStudents > 0 ? round(($studentsLate / $totalStudents) * 100) : 0;
        $studentAbsentPercent = $totalStudents > 0 ? round(($studentsAbsent / $totalStudents) * 100) : 0;

        // Employee attendance today
        $employeeAttendanceToday = EmployeeAttendance::whereDate('date', $today);
        $employeesPresent = (clone $employeeAttendanceToday)->where('status', 'Present')->count();
        $employeesLate = (clone $employeeAttendanceToday)->where('status', 'Late')->count();
        $employeesAbsent = (clone $employeeAttendanceToday)->where('status', 'Absent')->count();
        $employeePresentPercent = $totalEmployees > 0 ? round(($employeesPresent / $totalEmployees) * 100) : 0;
        $employeeLatePercent = $totalEmployees > 0 ? round(($employeesLate / $totalEmployees) * 100) : 0;
        $employeeAbsentPercent = $totalEmployees > 0 ? round(($employeesAbsent / $totalEmployees) * 100) : 0;

        // Total present today (students + employees)
        $totalPresentToday = $studentsPresent + $studentsLate + $employeesPresent + $employeesLate;

        // Recent attendance records (combined students and employees)
        $recentStudentAttendance = StudentAttendance::with('student')
            ->whereDate('date', $today)
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($attendance) {
                return [
                    'name' => $attendance->student->full_name ?? 'Unknown',
                    'type' => 'Student',
                    'type_class' => 'student',
                    'id' => $attendance->student->student_id_number ?? '-',
                    'time_in' => $attendance->time_in ? date('h:i A', strtotime($attendance->time_in)) : '--',
                    'time_out' => $attendance->time_out ? date('h:i A', strtotime($attendance->time_out)) : '--',
                    'status' => ucfirst($attendance->status),
                    'status_badge' => '<span class="badge-status ' . $attendance->status_badge . '">' . ucfirst($attendance->status) . '</span>',
                    'photo' => $attendance->student->photo_path ?? null,
                    'created_at' => $attendance->created_at,
                ];
            });

        $recentEmployeeAttendance = EmployeeAttendance::with('employee')
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
            });

        // Merge and sort by created_at
        $recentAttendance = $recentStudentAttendance
            ->concat($recentEmployeeAttendance)
            ->sortByDesc('created_at')
            ->take(10)
            ->values();

        // Weekly attendance data for chart (last 7 days)
        $weeklyData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dayName = $date->format('D');
            
            $studentCount = StudentAttendance::whereDate('date', $date)
                ->whereIn('status', ['present', 'late'])
                ->count();
            
            $employeeCount = EmployeeAttendance::whereDate('date', $date)
                ->whereIn('status', ['Present', 'Late'])
                ->count();

            $weeklyData[] = [
                'day' => $dayName,
                'date' => $date->format('M d'),
                'students' => $studentCount,
                'employees' => $employeeCount,
            ];
        }

        return view('dashboard', compact(
            'totalStudents',
            'totalEmployees',
            'visitorsToday',
            'totalPresentToday',
            'studentsPresent',
            'studentsLate',
            'studentsAbsent',
            'studentPresentPercent',
            'studentLatePercent',
            'studentAbsentPercent',
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

