<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingNotification extends Model
{
    /** Customer or staff created a booking request (pending). */
    public const EVENT_BOOKING_REQUESTED = 'booking_requested';

    /**
     * @deprecated Use EVENT_BOOKING_REQUESTED — same event key, kept for call-site compatibility.
     */
    public const EVENT_BOOKING_CREATED = self::EVENT_BOOKING_REQUESTED;

    public const EVENT_BOOKING_ACCEPTED = 'booking_accepted';

    public const EVENT_BOOKING_REJECTED = 'booking_rejected';

    public const EVENT_BOOKING_CANCELLED = 'booking_cancelled';

    public const EVENT_BOOKING_ARRIVED = 'booking_arrived';

    public const EVENT_BOOKING_SEATED = 'booking_seated';

    public const EVENT_BOOKING_COMPLETED = 'booking_completed';

    public const EVENT_BOOKING_NO_SHOW = 'booking_no_show';

    /**
     * Reserved for future scheduled reminders — no automatic enqueue in Phase 7B.
     */
    public const EVENT_BOOKING_ARRIVAL_REMINDER = 'booking_arrival_reminder';

    /** @var list<string> */
    public const EVENTS = [
        self::EVENT_BOOKING_REQUESTED,
        self::EVENT_BOOKING_ACCEPTED,
        self::EVENT_BOOKING_REJECTED,
        self::EVENT_BOOKING_CANCELLED,
        self::EVENT_BOOKING_ARRIVED,
        self::EVENT_BOOKING_SEATED,
        self::EVENT_BOOKING_COMPLETED,
        self::EVENT_BOOKING_NO_SHOW,
        self::EVENT_BOOKING_ARRIVAL_REMINDER,
    ];

    protected $fillable = [
        'booking_id',
        'user_id',
        'channel',
        'event',
        'recipient_phone',
        'recipient_name',
        'title',
        'message',
        'status',
        'payload',
        'sent_at',
        'failed_at',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dispatchAttempts(): HasMany
    {
        return $this->hasMany(NotificationDispatchAttempt::class);
    }
}
