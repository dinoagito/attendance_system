<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeFingerprint extends Model
{
    protected $table = 'employee_fingerprints';

    protected $fillable = [
        'employee_id',
        'fingerprint_template',
        'finger_index',
        'quality_score',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
