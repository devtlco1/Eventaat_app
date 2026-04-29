<?php

namespace App\Models;

use App\Enums\BranchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $fillable = [
        'restaurant_id',
        'name',
        'code',
        'status',
    ];

    protected $casts = [
        'status' => BranchStatus::class,
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function seatingAreas(): HasMany
    {
        return $this->hasMany(SeatingArea::class);
    }

    public function staffAssignments(): HasMany
    {
        return $this->hasMany(RestaurantStaffAssignment::class);
    }
}
