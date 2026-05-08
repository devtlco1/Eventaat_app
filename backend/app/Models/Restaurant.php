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

    public function subscriptions(): HasMany
    {
        return $this->hasMany(RestaurantSubscription::class)->orderByDesc('starts_at');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(RestaurantInvoice::class)->orderByDesc('issue_date')->orderByDesc('id');
    }

    public function callCenterCalls(): HasMany
    {
        return $this->hasMany(CallCenterCall::class)->orderByDesc('created_at');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(RestaurantReview::class);
    }
}
