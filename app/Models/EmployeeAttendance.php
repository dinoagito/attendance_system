<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeAttendance extends Model
{
    protected $table = 'employee_attendances';

    protected $fillable = [
        'employee_id',
        'date',
        'time_in',
        'time_out',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the employee this attendance belongs to
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'Present' => 'badge-on-time',
            'Late' => 'badge-late',
            'Absent' => 'badge-absent',
            'Undertime' => 'badge-late',
            'Leave' => 'badge-excused',
        ];

        return $badges[$this->status] ?? 'badge-default';
    }

    /**
     * Get formatted time in
     */
    public function getFormattedTimeInAttribute()
    {
        return $this->time_in ? date('h:i A', strtotime($this->time_in)) : '--';
    }

    /**
     * Get formatted time out
     */
    public function getFormattedTimeOutAttribute()
    {
        return $this->time_out ? date('h:i A', strtotime($this->time_out)) : '--';
    }
}
