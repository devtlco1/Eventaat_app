<?php

namespace App\Models;

use App\Enums\RestaurantStaffRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantStaffAssignment extends Model
{
    protected $fillable = [
        'user_id',
        'restaurant_id',
        'branch_id',
        'role',
        'status',
    ];

    protected $casts = [
        'role' => RestaurantStaffRole::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
