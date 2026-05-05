<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'credential_id',
        'public_key',
        'device_name',
        'sign_count',
    ];

    /**
     * Get the employee that owns the credential
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
