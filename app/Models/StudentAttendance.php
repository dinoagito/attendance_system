<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model
{
    protected $table = 'student_attendances';

    protected $fillable = [
        'student_id',
        'date',
        'time_in',
        'time_out',
        'status',
        'remarks',
        'confirmed_status',
        'confirmed_by',
        'confirmed_at',
        'rejection_reason',
        'approval_notes',
    ];

    protected $casts = [
        'date' => 'date',
        'confirmed_at' => 'datetime',
    ];

    /**
     * Get the student this attendance belongs to
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Get the user who confirmed this attendance
     */
    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'present' => 'badge-on-time',
            'late' => 'badge-late',
            'absent' => 'badge-absent',
            'excused' => 'badge-excused',
        ];

        return $badges[$this->status] ?? 'badge-default';
    }

    /**
     * Get readable status
     */
    public function getReadableStatusAttribute()
    {
        $statuses = [
            'present' => 'On Time',
            'late' => 'Late',
            'absent' => 'Absent',
            'excused' => 'Excused',
        ];

        return $statuses[$this->status] ?? ucfirst($this->status);
    }
}
