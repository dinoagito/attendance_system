<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $table = 'employee_schedules';

    protected $fillable = [
        'employee_id',
        'user_type',
        'name',
        'day_of_week',
        'schedule_days',
        'schedule_group_key',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'schedule_days' => 'array',
    ];

    /**
     * Get the employee/faculty that owns this schedule
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Normalize a days list into unique ordered day names.
     */
    public static function normalizeDays(array $days): array
    {
        $dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $days = array_values(array_unique($days));

        usort($days, function ($firstDay, $secondDay) use ($dayOrder) {
            return array_search($firstDay, $dayOrder) <=> array_search($secondDay, $dayOrder);
        });

        return $days;
    }

    /**
     * Build a stable key for one logical schedule group for Employee/Faculty.
     */
    public static function buildGroupKey(string $userType, ?int $employeeId, ?int $studentId, array $days, string $startTime, string $endTime): string
    {
        // Student parameter kept for backward compatibility but ignored - system is now Employee/Faculty only
        $identifier = 'employee:' . $employeeId;
        return sha1($identifier . '|' . implode(',', $days) . '|' . $startTime . '|' . $endTime);
    }

    /**
     * Overload to support legacy calls with 6 params, but primary is employee-only
     */
    public static function buildGroupKeyForEmployee(int $employeeId, array $days, string $startTime, string $endTime): string
    {
        return self::buildGroupKey('employee', $employeeId, null, $days, $startTime, $endTime);
    }

    /**
     * Get the days for this schedule, regardless of storage format.
     */
    public function getDaysListAttribute(): array
    {
        if (!empty($this->schedule_days)) {
            return self::normalizeDays($this->schedule_days);
        }

        return $this->day_of_week ? [$this->day_of_week] : [];
    }

    /**
     * Get a readable days string.
     */
    public function getDaysDisplayAttribute(): string
    {
        $days = $this->days_list;

        if ($days === ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']) {
            return 'Mon-Fri (Weekdays)';
        }

        if ($days === ['Saturday', 'Sunday']) {
            return 'Sat-Sun (Weekend)';
        }

        if (count($days) === 7) {
            return 'Every Day';
        }

        $shortDays = [
            'Monday' => 'Mon',
            'Tuesday' => 'Tue',
            'Wednesday' => 'Wed',
            'Thursday' => 'Thu',
            'Friday' => 'Fri',
            'Saturday' => 'Sat',
            'Sunday' => 'Sun',
        ];

        return implode(', ', array_map(fn ($day) => $shortDays[$day] ?? $day, $days));
    }
}
