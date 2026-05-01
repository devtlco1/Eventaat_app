<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantEvent extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_CANCELLED,
        self::STATUS_COMPLETED,
    ];

    public const BOOKING_MODE_NORMAL = 'normal_booking';
    public const BOOKING_MODE_EVENT = 'event_booking';
    public const BOOKING_MODE_INFO_ONLY = 'info_only';

    /** @var list<string> */
    public const BOOKING_MODES = [
        self::BOOKING_MODE_NORMAL,
        self::BOOKING_MODE_EVENT,
        self::BOOKING_MODE_INFO_ONLY,
    ];

    protected $fillable = [
        'restaurant_id',
        'branch_id',
        'title',
        'slug',
        'description',
        'starts_at',
        'ends_at',
        'status',
        'booking_mode',
        'capacity',
        'price_label',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'capacity' => 'int',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'restaurant_event_id');
    }

    public function activeReservedSeats(): int
    {
        $statuses = [
            BookingStatus::Pending->value,
            BookingStatus::Accepted->value,
            BookingStatus::Arrived->value,
            BookingStatus::Seated->value,
        ];

        return (int) Booking::query()
            ->where('restaurant_event_id', $this->id)
            ->whereIn('status', $statuses)
            ->sum('party_size');
    }

    public function remainingSeats(): ?int
    {
        if ($this->capacity === null) {
            return null;
        }

        $remaining = (int) $this->capacity - $this->activeReservedSeats();

        return max(0, $remaining);
    }
}

