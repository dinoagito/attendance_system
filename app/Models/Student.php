<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $table = 'students';

    protected $fillable = [
        'student_id_number',
        'rfid_uid',
        'first_name',
        'last_name',
        'course',
        'section',
        'photo_path',
    ];

    /**
     * Get the attendance records for this student
     */
    public function attendances()
    {
        return $this->hasMany(StudentAttendance::class, 'student_id');
    }

    /**
     * Get student schedules
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'student_id');
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
