<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    protected $table = 'visitors';

    protected $fillable = [
        'full_name',
        'person_to_visit',
        'purpose',
        'date',
        'time_in',
        'time_out',
        'phone',
        'photo_path',
        'remarks',
    ];

    protected $casts = [
        'date' => 'date',
        'time_in' => 'datetime',
        'time_out' => 'datetime',
    ];

    /**
     * Get formatted check-in time
     */
    public function getCheckInTimeAttribute()
    {
        return $this->time_in ? $this->time_in->format('h:i A') : '-';
    }

    /**
     * Get formatted check-out time
     */
    public function getCheckOutTimeAttribute()
    {
        return $this->time_out ? $this->time_out->format('h:i A') : '-';
    }

    /**
     * Get photo URL
     */
    public function getPhotoUrlAttribute()
    {
        return $this->photo_path ? asset($this->photo_path) : null;
    }

    /**
     * Get duration (time_in to time_out)
     */
    public function getDurationAttribute()
    {
        if (!$this->time_in) {
            return '-';
        }

        $timeIn = $this->time_in;
        $timeOut = $this->time_out ?? now();
        
        $diff = $timeIn->diffInSeconds($timeOut);

        $hours = floor($diff / 3600);
        $minutes = floor(($diff % 3600) / 60);
        $seconds = $diff % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        } else if ($minutes > 0) {
            return "{$minutes}m";
        } else {
            return "{$seconds}s";
        }
    }

    /**
     * Get the employee being visited
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'person_to_visit');
    }

    /**
     * Get the displayed name for the person being visited
     */
    public function getPersonToVisitNameAttribute()
    {
        return $this->employee ? $this->employee->full_name : $this->person_to_visit;
    }

    /**
     * Get status string for on-site visitors
     */
    public function getStatusStringAttribute()
    {
        return $this->time_out ? 'Checked Out' : 'On-site';
    }
}
