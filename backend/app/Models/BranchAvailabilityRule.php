<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchAvailabilityRule extends Model
{
    protected $fillable = [
        'branch_id',
        'is_booking_enabled',
        'booking_duration_minutes',
        'min_advance_minutes',
        'max_advance_days',
        'open_time',
        'close_time',
        'mon',
        'tue',
        'wed',
        'thu',
        'fri',
        'sat',
        'sun',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_booking_enabled' => 'boolean',
            'booking_duration_minutes' => 'integer',
            'min_advance_minutes' => 'integer',
            'max_advance_days' => 'integer',
            'mon' => 'boolean',
            'tue' => 'boolean',
            'wed' => 'boolean',
            'thu' => 'boolean',
            'fri' => 'boolean',
            'sat' => 'boolean',
            'sun' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
