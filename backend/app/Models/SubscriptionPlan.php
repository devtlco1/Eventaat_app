<?php

namespace App\Models;

use App\Enums\SubscriptionBillingInterval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_amount',
        'currency',
        'billing_interval',
        'is_active',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'price_amount' => 'decimal:2',
            'billing_interval' => SubscriptionBillingInterval::class,
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function restaurantSubscriptions(): HasMany
    {
        return $this->hasMany(RestaurantSubscription::class);
    }
}
