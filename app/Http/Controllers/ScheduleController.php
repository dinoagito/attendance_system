<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\Employee;

class ScheduleController extends Controller
{
    /**
     * Convert HH:MM or HH:MM:SS into minutes from midnight.
     */
    private function toMinutes(string $time): int
    {
        $parts = explode(':', $time);
        $hours = (int) ($parts[0] ?? 0);
        $minutes = (int) ($parts[1] ?? 0);

        return ($hours * 60) + $minutes;
    }

    /**
     * Check overlapping schedules for the same employee (faculty).
     */
    private function findScheduleConflict(
        ?int $employeeId,
        array $days,
        string $startTime,
        string $endTime,
        ?Schedule $excludeSchedule = null
    ): ?string {
        $query = Schedule::query()->where('employee_id', $employeeId);

        $existingSchedules = $query->get();
        $inputStart = $this->toMinutes($startTime);
        $inputEnd = $this->toMinutes($endTime);

        foreach ($existingSchedules as $existingSchedule) {
            if ($excludeSchedule) {
                if ($excludeSchedule->schedule_group_key && $existingSchedule->schedule_group_key === $excludeSchedule->schedule_group_key) {
                    continue;
                }

                if (
                    !$excludeSchedule->schedule_group_key
                    && !$existingSchedule->schedule_group_key
                    && $existingSchedule->employee_id == $excludeSchedule->employee_id
                    && $existingSchedule->start_time == $excludeSchedule->start_time
                    && $existingSchedule->end_time == $excludeSchedule->end_time
                ) {
                    continue;
                }
            }

            $existingDays = !empty($existingSchedule->schedule_days)
                ? Schedule::normalizeDays($existingSchedule->schedule_days)
                : ($existingSchedule->day_of_week ? [$existingSchedule->day_of_week] : []);

            $sharedDays = array_values(array_intersect($days, $existingDays));
            if (empty($sharedDays)) {
                continue;
            }

            $existingStart = $this->toMinutes($existingSchedule->start_time);
            $existingEnd = $this->toMinutes($existingSchedule->end_time);
            $hasOverlap = $inputStart < $existingEnd && $inputEnd > $existingStart;

            if ($hasOverlap) {
                return 'Schedule conflict on ' . implode(', ', $sharedDays)
                    . ' with existing time ' . substr($existingSchedule->start_time, 0, 5)
                    . '-' . substr($existingSchedule->end_time, 0, 5) . '.';
            }
        }

        return null;
    }

    /**
     * Display schedule management page for Employees/Faculty - strictly employee-specific.
     * Requires valid employee_id query param; otherwise redirects to Employee Management.
     */
    public function index(Request $request)
    {
        $filterEmployeeId = $request->query('employee_id');

        // Strict scoping: employee_id is required and must be valid
        if (!$filterEmployeeId || !ctype_digit((string) $filterEmployeeId)) {
            return redirect()->route('users.index')->with('error', 'Please select an employee from Employee Management to view their schedules.');
        }

        $filteredEmployee = Employee::find($filterEmployeeId);
        if (!$filteredEmployee) {
            return redirect()->route('users.index')->with('error', 'Selected employee not found.');
        }

        $schedules = Schedule::with('employee')
            ->where('employee_id', $filterEmployeeId)
            ->orderBy('employee_id')
            ->get();
        
        $groupedSchedules = $schedules->groupBy(function ($schedule) {
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
                'ids' => $group->pluck('id')->toArray(),
                'employee' => $first->employee,
                'employee_id' => $first->employee_id,
                'days' => $days,
                'days_display' => $this->formatDays($days),
                'start_time' => $first->start_time,
                'end_time' => $first->end_time,
            ];
        })->values();

        return view('schedule.index', [
            'schedules' => $groupedSchedules,
            'filterEmployeeId' => (int) $filterEmployeeId,
            'filteredEmployee' => $filteredEmployee,
        ]);
    }

    /**
     * Format days array into a readable string
     */
    private function formatDays(array $days): string
    {
        $dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        usort($days, function($a, $b) use ($dayOrder) {
            return array_search($a, $dayOrder) - array_search($b, $dayOrder);
        });
        
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
     * Store a new schedule for Employee/Faculty - strictly scoped, appends to existing.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'schedule_days' => 'required|array|min:1',
            'schedule_start_time' => 'required|date_format:H:i',
            'schedule_end_time' => 'required|date_format:H:i',
        ]);

        // Backend enforcement: employee scope is source of truth; validate exists
        $employee = Employee::find($validated['employee_id']);
        if (!$employee) {
            return redirect()->route('users.index')->with('error', 'Selected employee not found.')->withInput();
        }

        $days = Schedule::normalizeDays($validated['schedule_days']);
        $startTime = $validated['schedule_start_time'];
        $endTime = $validated['schedule_end_time'];

        if ($this->toMinutes($endTime) <= $this->toMinutes($startTime)) {
            return redirect()->route('schedule.index', ['employee_id' => $validated['employee_id']])->with('error', 'Time Out must be later than Time In.')->withInput();
        }

        $conflictMessage = $this->findScheduleConflict(
            $validated['employee_id'],
            $days,
            $startTime,
            $endTime
        );

        if ($conflictMessage) {
            return redirect()->route('schedule.index', ['employee_id' => $validated['employee_id']])->with('error', $conflictMessage)->withInput();
        }

        $groupKey = Schedule::buildGroupKey(
            'employee',
            $validated['employee_id'],
            null,
            $days,
            $startTime,
            $endTime
        );

        Schedule::updateOrCreate(
            ['schedule_group_key' => $groupKey],
            [
                'employee_id' => $validated['employee_id'],
                'day_of_week' => $days[0],
                'schedule_days' => $days,
                'start_time' => $startTime,
                'end_time' => $endTime,
            ]
        );

        return redirect()->route('schedule.index', ['employee_id' => $validated['employee_id']])->with('success', 'Schedule created successfully');
    }

    /**
     * Update schedule for Employee/Faculty - locked to current employee.
     */
    public function update(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'schedule_days' => 'required|array|min:1',
            'schedule_start_time' => 'required|date_format:H:i',
            'schedule_end_time' => 'required|date_format:H:i',
        ]);

        // Backend enforcement: lock to current employee - prevent moving schedule to another employee
        if ((int) $validated['employee_id'] !== (int) $schedule->employee_id) {
            return redirect()->route('schedule.index', ['employee_id' => $schedule->employee_id])->with('error', 'Cannot change employee for this schedule. The schedule is locked to its current employee.')->withInput();
        }

        $days = Schedule::normalizeDays($validated['schedule_days']);
        $startTime = $validated['schedule_start_time'];
        $endTime = $validated['schedule_end_time'];

        if ($this->toMinutes($endTime) <= $this->toMinutes($startTime)) {
            return redirect()->route('schedule.index', ['employee_id' => $schedule->employee_id])->with('error', 'Time Out must be later than Time In.')->withInput();
        }

        $conflictMessage = $this->findScheduleConflict(
            $validated['employee_id'],
            $days,
            $startTime,
            $endTime,
            $schedule
        );

        if ($conflictMessage) {
            return redirect()->route('schedule.index', ['employee_id' => $schedule->employee_id])->with('error', $conflictMessage)->withInput();
        }

        $groupKey = Schedule::buildGroupKey(
            'employee',
            $validated['employee_id'],
            null,
            $days,
            $startTime,
            $endTime
        );

        if ($schedule->schedule_group_key) {
            $conflict = Schedule::where('schedule_group_key', $groupKey)
                ->where('id', '!=', $schedule->id)
                ->exists();

            if ($conflict) {
                return redirect()->route('schedule.index', ['employee_id' => $schedule->employee_id])->with('error', 'A schedule with the same user, days, and time already exists.');
            }

            $schedule->update([
                'employee_id' => $validated['employee_id'],
                'day_of_week' => $days[0],
                'schedule_days' => $days,
                'schedule_group_key' => $groupKey,
                'start_time' => $startTime,
                'end_time' => $endTime,
            ]);
        } else {
            Schedule::where('employee_id', $schedule->employee_id)
                ->where('start_time', $schedule->start_time)
                ->where('end_time', $schedule->end_time)
                ->delete();

            Schedule::updateOrCreate(
                ['schedule_group_key' => $groupKey],
                [
                    'employee_id' => $validated['employee_id'],
                    'day_of_week' => $days[0],
                    'schedule_days' => $days,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ]
            );
        }

        return redirect()->route('schedule.index', ['employee_id' => $schedule->employee_id])->with('success', 'Schedule updated successfully');
    }

    /**
     * Delete schedule (deletes all days for the same person/time) - scoped redirect
     */
    public function destroy(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);
        $employeeId = $schedule->employee_id;

        if ($schedule->schedule_group_key) {
            $schedule->delete();
        } else {
            Schedule::where('employee_id', $schedule->employee_id)
                ->where('start_time', $schedule->start_time)
                ->where('end_time', $schedule->end_time)
                ->delete();
        }
            
        return redirect()->route('schedule.index', ['employee_id' => $employeeId])->with('success', 'Schedule deleted successfully');
    }
}
