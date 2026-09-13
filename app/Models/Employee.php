<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'employees';

    protected $fillable = [
        'employee_id_number',
        'first_name',
        'last_name',
        'biometric_data',
        'photo_path',
        'department',
        'email',
        'phone',
        'status',
    ];

    /**
     * Get the full name attribute
     */
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get the name attribute (alias for full_name)
     */
    public function getNameAttribute()
    {
        return $this->full_name;
    }

    /**
     * Get employee attendances
     */
    public function attendances()
    {
        return $this->hasMany(EmployeeAttendance::class, 'employee_id');
    }

    /**
     * Get employee schedules
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'employee_id');
    }

    /**
     * Get employee WebAuthn credential (Windows Hello)
     */
    public function credential()
    {
        return $this->hasOne(EmployeeCredential::class, 'employee_id');
    }

    /**
     * Get employee ZK9500 fingerprints
     */
    public function fingerprints()
    {
        return $this->hasMany(EmployeeFingerprint::class, 'employee_id')->where('is_active', 1);
    }

    /**
     * Check if employee has enrolled fingerprint
     */
    public function getIsEnrolledAttribute(): bool
    {
        if (array_key_exists('fingerprints_count', $this->attributes)) {
            return (int) $this->fingerprints_count > 0;
        }
        return $this->fingerprints()->exists();
    }
}
