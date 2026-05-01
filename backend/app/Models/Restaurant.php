<?php

namespace App\Models;

use App\Enums\RestaurantStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'status',
    ];

    protected $casts = [
        'status' => RestaurantStatus::class,
    ];

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function staffAssignments(): HasMany
    {
        return $this->hasMany(RestaurantStaffAssignment::class);
    }

    public function menus(): HasMany
    {
        return $this->hasMany(RestaurantMenu::class);
    }
}
