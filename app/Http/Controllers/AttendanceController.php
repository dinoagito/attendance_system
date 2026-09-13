<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\Visitor;

class AttendanceController extends Controller
{
    /**
     * Show employee attendance report grouped by employee
     */
    public function employeeAttendance(Request $request)
    {
        // Show no records until Search (with a filter) or All Employees is clicked.
        $hasFilters = $request->hasAny(['department', 'employee', 'date_from', 'date_to'])
            || $request->filled('action');

        $employees = null;

        if ($hasFilters) {
            $dateFrom = $request->filled('date_from') ? $request->date_from : null;
            $dateTo = $request->filled('date_to') ? $request->date_to : null;

            $employees = $this->buildEmployeeAttendanceQuery($request)
                ->with(['attendances' => function ($query) use ($dateFrom, $dateTo) {
                    if ($dateFrom) {
                        $query->whereDate('date', '>=', $dateFrom);
                    }
                    if ($dateTo) {
                        $query->whereDate('date', '<=', $dateTo);
                    }
                    $query->orderBy('date', 'asc');
                }])
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->paginate(15)
                ->withQueryString();
        }

        // Get unique departments for the dropdown
        $departments = Employee::distinct()
            ->pluck('department')
            ->filter()
            ->sort()
            ->values();

        return view('attendance.employee', [
            'employees' => $employees,
            'departments' => $departments,
            'filters' => $this->resolveFilters($request),
        ]);
    }

    /**
     * Printable employee attendance report (browser print / Save as PDF)
     */
    public function employeeAttendancePrint(Request $request)
    {
        $dateFrom = $request->filled('date_from') ? $request->date_from : null;
        $dateTo = $request->filled('date_to') ? $request->date_to : null;

        $employees = $this->buildEmployeeAttendanceQuery($request)
            ->with(['attendances' => function ($query) use ($dateFrom, $dateTo) {
                if ($dateFrom) {
                    $query->whereDate('date', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $query->whereDate('date', '<=', $dateTo);
                }
                $query->orderBy('date', 'asc');
            }])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $departments = Employee::distinct()
            ->pluck('department')
            ->filter()
            ->sort()
            ->values();

        return view('attendance.employee-print', [
            'employees' => $employees,
            'departments' => $departments,
            'filters' => $this->resolveFilters($request),
            'generatedAt' => now(),
        ]);
    }

    /**
     * Build the employee query applying department and name / employee no. filters.
     */
    private function buildEmployeeAttendanceQuery(Request $request)
    {
        $query = Employee::query();

        if ($request->filled('department') && $request->department !== 'all') {
            $query->where('department', $request->department);
        }

        $action = $request->input('action');

        // Empty employee field = All Employees. The "All Employees" button
        // (action=all) also ignores the employee text so name/number is cleared.
        if ($request->filled('employee') && $action !== 'all') {
            $searchTerm = trim($request->employee);
            $query->where(function ($query) use ($searchTerm) {
                $query->where('employee_id_number', 'like', '%' . $searchTerm . '%')
                    ->orWhere('first_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('last_name', 'like', '%' . $searchTerm . '%');
            });
        }

        return $query;
    }

    /**
     * Resolve the filter values for display, clearing the employee field
     * when the "All Employees" action was used.
     */
    private function resolveFilters(Request $request)
    {
        $filters = $request->only(['department', 'employee', 'date_from', 'date_to']);
        if ($request->input('action') === 'all') {
            $filters['employee'] = '';
        }
        return $filters;
    }

    /**
     * Show employee attendance calendar
     */
    public function employeeCalendar($employeeId)
    {
        $employee = Employee::findOrFail($employeeId);
        $attendances = EmployeeAttendance::where('employee_id', $employeeId)
            ->orderBy('date', 'desc')
            ->get();

        // Group by month for calendar view
        $attendancesByMonth = $attendances->groupBy(function ($date) {
            return $date->date->format('Y-m');
        });

        return view('attendance.employee-calendar', [
            'employee' => $employee,
            'attendances' => $attendances,
            'attendancesByMonth' => $attendancesByMonth,
        ]);
    }

    /**
     * Add manual attendance for an employee
     */
    public function addEmployeeAttendance(Request $request, $employeeId)
    {
        $request->validate([
            'date' => 'required|date',
            'time_in' => 'nullable|date_format:H:i',
            'time_out' => 'nullable|date_format:H:i',
            'status' => 'required|in:Present,Late,Absent,Undertime,Leave',
        ]);

        $employee = Employee::findOrFail($employeeId);

        // Check if attendance already exists for this date
        $existingAttendance = EmployeeAttendance::where('employee_id', $employeeId)
            ->whereDate('date', $request->date)
            ->first();

        if ($existingAttendance) {
            return redirect()->back()->with('error', 'Attendance already exists for this date. Please edit the existing record.');
        }

        // Create attendance record
        EmployeeAttendance::create([
            'employee_id' => $employeeId,
            'date' => $request->date,
            'time_in' => $request->time_in ? $request->date . ' ' . $request->time_in . ':00' : null,
            'time_out' => $request->time_out ? $request->date . ' ' . $request->time_out . ':00' : null,
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Attendance recorded successfully for ' . $employee->full_name);
    }

    /**
     * Show visitor log with filters
     */
    public function visitorLog(Request $request)
    {
        $query = Visitor::query();
        $employees = Employee::orderBy('first_name')->orderBy('last_name')->get();

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        // Filter by purpose
        if ($request->filled('purpose') && $request->purpose !== '') {
            $query->where('purpose', 'like', '%' . $request->purpose . '%');
        }

        // Filter by status
        if ($request->filled('status') && $request->status !== '') {
            if ($request->status === 'checked_in') {
                $query->whereNull('time_out');
            } elseif ($request->status === 'checked_out') {
                $query->whereNotNull('time_out');
            }
        }

        // Order by date descending
        $visitors = $query->with('employee')->orderBy('date', 'desc')->paginate(15);

        return view('attendance.visitor', [
            'visitors' => $visitors,
            'employees' => $employees,
            'filters' => $request->only(['date_from', 'date_to', 'purpose', 'status']),
        ]);
    }

    /**
     * Export visitor log to CSV
     */
    public function exportVisitorLog(Request $request)
    {
        $query = Visitor::query();

        // Apply same filters as visitorLog
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('purpose') && $request->purpose !== '') {
            $query->where('purpose', 'like', '%' . $request->purpose . '%');
        }

        if ($request->filled('status') && $request->status !== '') {
            if ($request->status === 'checked_in') {
                $query->whereNull('time_out');
            } elseif ($request->status === 'checked_out') {
                $query->whereNotNull('time_out');
            }
        }

        $visitors = $query->with('employee')->orderBy('date', 'desc')->get();

        // Create CSV
        $filename = 'visitor_log_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ];

        $callback = function() use ($visitors) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fwrite($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            
            // Add CSV headers
            fputcsv($file, ['Name', 'Phone', 'Person to Visit', 'Purpose', 'Date', 'Check In', 'Check Out', 'Status']);

            // Add data rows
            foreach ($visitors as $visitor) {
                $timeIn = $visitor->time_in ? \Carbon\Carbon::createFromFormat('H:i:s', $visitor->time_in)->format('h:i A') : '-';
                $timeOut = $visitor->time_out ? \Carbon\Carbon::createFromFormat('H:i:s', $visitor->time_out)->format('h:i A') : '-';
                $status = $visitor->time_out ? 'Checked Out' : 'Checked In';

                fputcsv($file, [
                    $visitor->full_name,
                    $visitor->phone ?? '-',
                    $visitor->person_to_visit_name ?? '-',
                    $visitor->purpose,
                    $visitor->date ? $visitor->date->format('M d, Y') : '-',
                    $timeIn,
                    $timeOut,
                    $status
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
