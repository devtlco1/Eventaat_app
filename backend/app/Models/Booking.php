<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = [
        'customer_id',
        'restaurant_id',
        'branch_id',
        'seating_area_id',
        'restaurant_table_id',
        'starts_at',
        'party_size',
        'status',
        'customer_note',
        'restaurant_note',
        'accepted_at',
        'rejected_at',
        'cancelled_at',
        'arrived_at',
        'seated_at',
        'completed_at',
        'no_show_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'party_size' => 'integer',
        'status' => BookingStatus::class,
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'arrived_at' => 'datetime',
        'seated_at' => 'datetime',
        'completed_at' => 'datetime',
        'no_show_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function seatingArea(): BelongsTo
    {
        return $this->belongsTo(SeatingArea::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'restaurant_table_id');
    }
}

