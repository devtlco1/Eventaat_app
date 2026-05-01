<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDispatchAttempt extends Model
{
    protected $fillable = [
        'booking_notification_id',
        'provider',
        'channel',
        'status',
        'request_payload',
        'response_payload',
        'provider_message_id',
        'failure_reason',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
            'attempted_at' => 'datetime',
        ];
    }

    public function bookingNotification(): BelongsTo
    {
        return $this->belongsTo(BookingNotification::class);
    }
}

