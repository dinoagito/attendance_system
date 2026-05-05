<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $dayOrder = [
            'Monday' => 1,
            'Tuesday' => 2,
            'Wednesday' => 3,
            'Thursday' => 4,
            'Friday' => 5,
            'Saturday' => 6,
            'Sunday' => 7,
        ];

        $groups = DB::table('employee_schedules')
            ->select('employee_id', 'student_id', 'user_type', 'start_time', 'end_time')
            ->groupBy('employee_id', 'student_id', 'user_type', 'start_time', 'end_time')
            ->get();

        foreach ($groups as $group) {
            $rows = DB::table('employee_schedules')
                ->where('employee_id', $group->employee_id)
                ->where('student_id', $group->student_id)
                ->where('user_type', $group->user_type)
                ->where('start_time', $group->start_time)
                ->where('end_time', $group->end_time)
                ->orderBy('id')
                ->get();

            if ($rows->isEmpty()) {
                continue;
            }

            $days = [];
            foreach ($rows as $row) {
                if (!empty($row->schedule_days)) {
                    $decoded = json_decode($row->schedule_days, true);
                    if (is_array($decoded)) {
                        $days = array_merge($days, $decoded);
                    }
                }

                if (!empty($row->day_of_week)) {
                    $days[] = $row->day_of_week;
                }
            }

            $days = array_values(array_unique($days));
            usort($days, function ($firstDay, $secondDay) use ($dayOrder) {
                return ($dayOrder[$firstDay] ?? 999) <=> ($dayOrder[$secondDay] ?? 999);
            });

            if (empty($days)) {
                continue;
            }

            $identity = $group->user_type === 'employee'
                ? 'employee:' . ($group->employee_id ?? 'null')
                : ($group->user_type === 'student'
                    ? 'student:' . ($group->student_id ?? 'null')
                    : 'template:null');

            $groupKey = sha1($identity . '|' . implode(',', $days) . '|' . $group->start_time . '|' . $group->end_time);

            $keepRow = $rows->first();

            DB::table('employee_schedules')
                ->where('id', $keepRow->id)
                ->update([
                    'day_of_week' => $days[0],
                    'schedule_days' => json_encode($days),
                    'schedule_group_key' => $groupKey,
                    'updated_at' => now(),
                ]);

            $deleteIds = $rows->pluck('id')->filter(fn ($id) => $id !== $keepRow->id)->values();
            if ($deleteIds->isNotEmpty()) {
                DB::table('employee_schedules')->whereIn('id', $deleteIds)->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Backfill is not reversible without restoring deleted legacy rows.
    }
};
