<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Employee;
use App\Models\Visitor;
use App\Models\Schedule;

class UserController extends Controller
{
    /**
     * Generate next ascending employee ID in the format EMP-YYYY-###.
     */
    private function generateNextEmployeeId(): string
    {
        $year = now()->format('Y');
        $prefix = "EMP-{$year}-";

        $existingIds = Employee::query()
            ->where('employee_id_number', 'like', $prefix . '%')
            ->pluck('employee_id_number');

        $maxSequence = 0;
        foreach ($existingIds as $employeeIdNumber) {
            if (preg_match('/^EMP-\d{4}-(\d+)$/', (string) $employeeIdNumber, $matches)) {
                $maxSequence = max($maxSequence, (int) $matches[1]);
            }
        }

        $nextSequence = $maxSequence + 1;
        $candidate = sprintf('EMP-%s-%03d', $year, $nextSequence);

        // Safety guard for rare race conditions.
        while (Employee::where('employee_id_number', $candidate)->exists()) {
            $nextSequence++;
            $candidate = sprintf('EMP-%s-%03d', $year, $nextSequence);
        }

        return $candidate;
    }

    /**
     * Build employees query with optional status and search filters.
     */
    private function buildEmployeeQuery(Request $request)
    {
        $query = Employee::query()->withCount('fingerprints');

        if ($request->filled('employee_status') && in_array($request->employee_status, ['active', 'inactive'])) {
            $query->where('status', $request->employee_status);
        }

        if ($request->filled('employee_search')) {
            $keyword = trim((string) $request->employee_search);
            $query->where(function ($q) use ($keyword) {
                $q->where('first_name', 'like', "%{$keyword}%")
                    ->orWhere('last_name', 'like', "%{$keyword}%")
                    ->orWhere('employee_id_number', 'like', "%{$keyword}%")
                    ->orWhere('department', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        return $query->orderBy('employee_id_number', 'asc');
    }

    /**
     * Format days array into a readable string
     */
    private function formatDays(array $days): string
    {
        $shortDays = [
            'Monday' => 'Mon',
            'Tuesday' => 'Tue', 
            'Wednesday' => 'Wed',
            'Thursday' => 'Thu',
            'Friday' => 'Fri',
            'Saturday' => 'Sat',
            'Sunday' => 'Sun'
        ];
        
        $shortNames = array_map(fn($day) => $shortDays[$day] ?? $day, $days);
        
        // Check for common patterns
        if ($days === ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']) {
            return 'Mon-Fri (Weekdays)';
        }
        if ($days === ['Saturday', 'Sunday']) {
            return 'Sat-Sun (Weekend)';
        }
        if (count($days) === 7) {
            return 'Every Day';
        }
        
        return implode(', ', $shortNames);
    }

    /**
     * Resolve the day list from a stored schedule template (Employee/Faculty only).
     */
    private function resolveScheduleDays(Schedule $scheduleTemplate): array
    {
        if (!empty($scheduleTemplate->schedule_days)) {
            return Schedule::normalizeDays($scheduleTemplate->schedule_days);
        }

        $relatedSchedules = Schedule::query()
            ->where('start_time', $scheduleTemplate->start_time)
            ->where('end_time', $scheduleTemplate->end_time)
            ->where('employee_id', $scheduleTemplate->employee_id)
            ->get();

        if ($relatedSchedules->isNotEmpty()) {
            return Schedule::normalizeDays($relatedSchedules->pluck('day_of_week')->filter()->values()->toArray());
        }

        return $scheduleTemplate->day_of_week ? [$scheduleTemplate->day_of_week] : [];
    }

    /**
     * Display all users with tabs for employees/faculty and visitors (Student removed)
     */
    public function index(Request $request)
    {
        $showAllEmployees = $request->boolean('show_all_employees');
        $newEmployeeId = $request->integer('new_employee_id');

        $hasEmployeeFilters = $request->filled('employee_status') || $request->filled('employee_search');
        $shouldShowEmployeeList = $showAllEmployees || $hasEmployeeFilters || $newEmployeeId;

        if ($newEmployeeId) {
            $newEmployeeQuery = Employee::where('id', $newEmployeeId)->withCount('fingerprints')
                ->orderBy('last_name')
                ->orderBy('first_name');

            $employees = $newEmployeeQuery->paginate(15, ['*'], 'employee_page')
                ->appends($request->query());
        } elseif ($shouldShowEmployeeList) {
            $employees = $this->buildEmployeeQuery($request)
                ->paginate(15, ['*'], 'employee_page')
                ->appends($request->query());
        } else {
            $employees = new LengthAwarePaginator([], 0, 15, 1, [
                'path' => url()->current(),
                'query' => $request->query(),
                'pageName' => 'employee_page',
            ]);
        }

        $visitors = Visitor::with('employee')->orderBy('date', 'desc')->paginate(15);
        $visitorEmployees = Employee::orderBy('first_name')->orderBy('last_name')->get();
        
        // Get schedule templates grouped by logical schedule group for Employees/Faculty
        $rawSchedules = Schedule::with('employee')
            ->select('id', 'employee_id', 'user_type', 'day_of_week', 'schedule_days', 'schedule_group_key', 'start_time', 'end_time')
            ->orderBy('employee_id')
            ->get();
        
        // Group schedules by logical schedule group
        $scheduleTemplates = $rawSchedules->groupBy(function($schedule) {
            if ($schedule->schedule_group_key) {
                return $schedule->schedule_group_key;
            }

            $personKey = $schedule->employee_id
                ? 'employee_' . $schedule->employee_id
                : 'template_' . $schedule->start_time . '_' . $schedule->end_time;

            return $personKey . '_' . $schedule->start_time . '_' . $schedule->end_time;
        })->map(function($group) {
            $first = $group->first();
            $days = $group->flatMap(function ($schedule) {
                return !empty($schedule->schedule_days)
                    ? $schedule->schedule_days
                    : ($schedule->day_of_week ? [$schedule->day_of_week] : []);
            })->unique()->values()->toArray();
            
            return (object)[
                'id' => $first->id,
                'days' => $days,
                'days_display' => $this->formatDays($days),
                'start_time' => $first->start_time,
                'end_time' => $first->end_time,
                'employee' => $first->employee,
                'user_type' => $first->user_type ?? 'employee',
            ];
        })->values();

        return view('users.index', [
            'employees' => $employees,
            'visitors' => $visitors,
            'visitorEmployees' => $visitorEmployees,
            'scheduleTemplates' => $scheduleTemplates,
            'showAllEmployees' => $showAllEmployees,
            'newEmployeeId' => $newEmployeeId,
            'shouldShowEmployeeList' => $shouldShowEmployeeList,
            'employeeFilters' => [
                'status' => $request->input('employee_status', ''),
                'search' => $request->input('employee_search', ''),
            ],
            'nextEmployeeId' => $this->generateNextEmployeeId(),
        ]);
    }

    /**
     * Printable employee list page.
     */
    public function printEmployees(Request $request)
    {
        $employees = $this->buildEmployeeQuery($request)->get();

        return view('users.employees-print', [
            'employees' => $employees,
            'employeeFilters' => [
                'status' => $request->input('employee_status', ''),
                'search' => $request->input('employee_search', ''),
            ],
        ]);
    }

    /**
     * Store a new employee/faculty or visitor (Student removed)
     */
    public function store(Request $request)
    {
        $type = $request->input('user_type');

        if ($type === 'employee') {
            $validated = $request->validate([
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'department' => 'nullable|string',
                'email' => 'nullable|email|unique:employees',
                'phone' => 'nullable|string',
                'status' => 'required|in:active,inactive',
                'schedule_id' => 'nullable|exists:employee_schedules,id',
            ]);

            $validated['employee_id_number'] = $this->generateNextEmployeeId();

            $employee = Employee::create($validated);

            // If a schedule template is selected, clone the grouped schedule to the new employee
            if ($request->filled('schedule_id')) {
                $scheduleTemplate = Schedule::find($request->input('schedule_id'));
                if ($scheduleTemplate) {
                    $days = $this->resolveScheduleDays($scheduleTemplate);

                    if ($days) {
                        $groupKey = Schedule::buildGroupKey(
                            'employee',
                            $employee->id,
                            null,
                            $days,
                            $scheduleTemplate->start_time,
                            $scheduleTemplate->end_time
                        );

                        Schedule::updateOrCreate(
                            ['schedule_group_key' => $groupKey],
                            [
                                'employee_id' => $employee->id,
                                'user_type' => 'employee',
                                'day_of_week' => $days[0],
                                'schedule_days' => $days,
                                'start_time' => $scheduleTemplate->start_time,
                                'end_time' => $scheduleTemplate->end_time,
                            ]
                        );
                    }
                }
            }

            return redirect()->route('users.index', [
                'tab' => 'employees',
                'new_employee_id' => $employee->id,
            ])->with('success', 'Employee added successfully. Showing the newly saved record.');
        } elseif ($type === 'visitor') {
            $validated = $request->validate([
                'full_name' => 'required|string',
                'person_to_visit' => 'nullable|exists:employees,id',
                'purpose' => 'required|string',
                'phone' => 'nullable|string',
                'date' => 'required|date',
                'time_in' => 'required|date_format:H:i',
            ]);

            Visitor::create($validated);

            return redirect()->route('users.index')->with('success', 'Visitor added successfully');
        }

        return redirect()->back()->with('error', 'Invalid user type');
    }

    /**
     * Update employee/faculty or visitor (Student removed)
     */
    public function update(Request $request, $id)
    {
        $type = $request->input('user_type');

        if ($type === 'employee') {
            $employee = Employee::findOrFail($id);
            $validated = $request->validate([
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'department' => 'nullable|string',
                'email' => 'nullable|email|unique:employees,email,' . $id,
                'phone' => 'nullable|string',
                'status' => 'required|in:active,inactive',
            ]);

            $employee->update($validated);

            return redirect()->route('users.index')->with('success', 'Employee updated successfully');
        } elseif ($type === 'visitor') {
            $visitor = Visitor::findOrFail($id);
            $validated = $request->validate([
                'full_name' => 'required|string',
                'phone' => 'nullable|string',
                'person_to_visit' => 'nullable|exists:employees,id',
                'purpose' => 'required|string',
                'date' => 'required|date',
                'time_in' => 'required|date_format:H:i',
                'time_out' => 'nullable|date_format:H:i',
            ]);

            $visitor->update($validated);

            return redirect()->route('users.index')->with('success', 'Visitor updated successfully');
        }

        return redirect()->back()->with('error', 'Invalid user type');
    }

    /**
     * Delete employee/faculty or visitor (Student removed)
     */
    public function destroy(Request $request, $id)
    {
        $type = $request->input('user_type');

        if ($type === 'employee') {
            $employee = Employee::findOrFail($id);

            if ($employee->status === 'active') {
                return redirect()->route('users.index', ['tab' => 'employees', 'show_all_employees' => 1])
                    ->with('error', 'Cannot delete an active employee. Set status to inactive first.');
            }

            $employee->delete();
            return redirect()->route('users.index', ['tab' => 'employees', 'show_all_employees' => 1])
                ->with('success', 'Employee deleted successfully');
        } elseif ($type === 'visitor') {
            Visitor::findOrFail($id)->delete();
            return redirect()->route('users.index')->with('success', 'Visitor deleted successfully');
        }

        return redirect()->back()->with('error', 'Invalid user type');
    }
}
