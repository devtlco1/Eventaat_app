<?php

namespace App\Models;

use App\Enums\SeatingAreaType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeatingArea extends Model
{
    protected $fillable = [
        'branch_id',
        'name',
        'code',
        'type',
        'status',
    ];

    protected $casts = [
        'type' => SeatingAreaType::class,
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(RestaurantTable::class, 'seating_area_id');
    }
}
