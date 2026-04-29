<?php

namespace App\Models;

use App\Enums\TableStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantTable extends Model
{
    protected $fillable = [
        'seating_area_id',
        'label',
        'capacity',
        'status',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'status' => TableStatus::class,
    ];

    public function seatingArea(): BelongsTo
    {
        return $this->belongsTo(SeatingArea::class, 'seating_area_id');
    }
}
