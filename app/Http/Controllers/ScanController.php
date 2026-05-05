<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use Carbon\Carbon;

class ScanController extends Controller
{
    /**
     * Show RFID student scan page
     */
    public function studentScan()
    {
        // Get today's scans
        $recentScans = StudentAttendance::with('student')
            ->whereDate('date', Carbon::today())
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        return view('scan.student', compact('recentScans'));
    }

    /**
     * Process student RFID scan
     */
    public function processStudentScan(Request $request)
    {
        $request->validate([
            'student_id' => 'required|string',
        ]);

        // Find student by ID number
        $student = Student::where('student_id_number', $request->student_id)->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found with ID: ' . $request->student_id
            ], 404);
        }

        $today = Carbon::today();
        $now = Carbon::now();

        // Check if student already has attendance for today
        $existingAttendance = StudentAttendance::where('student_id', $student->id)
            ->whereDate('date', $today)
            ->first();

        if ($existingAttendance) {
            // If time_out is null, record time out
            if (!$existingAttendance->time_out) {
                $existingAttendance->update([
                    'time_out' => $now,
                ]);

                return response()->json([
                    'success' => true,
                    'action' => 'time_out',
                    'message' => 'Time out recorded successfully',
                    'student' => [
                        'id' => $student->id,
                        'name' => $student->full_name,
                        'student_id_number' => $student->student_id_number,
                        'course' => $student->course,
                        'section' => $student->section,
                        'photo' => $student->photo_path,
                    ],
                    'attendance' => [
                        'time_in' => $existingAttendance->time_in ? date('h:i A', strtotime($existingAttendance->time_in)) : null,
                        'time_out' => $now->format('h:i A'),
                        'status' => $existingAttendance->status,
                    ],
                ]);
            }

            // Already has complete attendance
            return response()->json([
                'success' => false,
                'message' => 'Student already has complete attendance for today',
                'student' => [
                    'name' => $student->full_name,
                    'student_id_number' => $student->student_id_number,
                ],
                'attendance' => [
                    'time_in' => $existingAttendance->time_in ? date('h:i A', strtotime($existingAttendance->time_in)) : null,
                    'time_out' => $existingAttendance->time_out ? date('h:i A', strtotime($existingAttendance->time_out)) : null,
                    'status' => $existingAttendance->status,
                ],
            ], 400);
        }

        // Determine status based on time (8:00 AM is on time, after is late)
        $scheduleTime = Carbon::today()->setTime(8, 0, 0);
        $status = $now->gt($scheduleTime) ? 'late' : 'present';

        // Create new attendance record
        $attendance = StudentAttendance::create([
            'student_id' => $student->id,
            'date' => $today,
            'time_in' => $now,
            'status' => $status,
        ]);

        return response()->json([
            'success' => true,
            'action' => 'time_in',
            'message' => 'Attendance recorded successfully',
            'student' => [
                'id' => $student->id,
                'name' => $student->full_name,
                'student_id_number' => $student->student_id_number,
                'course' => $student->course,
                'section' => $student->section,
                'photo' => $student->photo_path,
            ],
            'attendance' => [
                'time_in' => $now->format('h:i A'),
                'time_out' => null,
                'status' => ucfirst($status),
            ],
        ]);
    }

    /**
     * Show biometric employee scan page
     */
    public function employeeScan()
    {
        return view('scan.employee');
    }
}
