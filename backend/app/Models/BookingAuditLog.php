<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAuditLog extends Model
{
    public const ACTION_ACCEPTED = 'accepted';

    public const ACTION_REJECTED = 'rejected';

    public const ACTION_CANCELLED = 'cancelled';

    public const ACTION_ARRIVED = 'arrived';

    public const ACTION_SEATED = 'seated';

    public const ACTION_COMPLETED = 'completed';

    public const ACTION_NO_SHOW = 'no_show';

    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'actor_id',
        'actor_type',
        'action',
        'from_status',
        'to_status',
        'message',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (BookingAuditLog $log): void {
            $log->created_at ??= now();
        });
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
