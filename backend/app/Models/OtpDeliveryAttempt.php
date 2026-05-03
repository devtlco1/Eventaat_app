<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpDeliveryAttempt extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'phone_hash',
        'phone_masked',
        'driver',
        'channel',
        'provider',
        'provider_message_sid',
        'status',
        'error_code',
        'error_message',
        'metadata',
        'created_at',
    ];

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
