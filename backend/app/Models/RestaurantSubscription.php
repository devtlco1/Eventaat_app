<?php

namespace App\Models;

use App\Enums\RestaurantSubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class RestaurantSubscription extends Model
{
    protected $fillable = [
        'restaurant_id',
        'subscription_plan_id',
        'status',
        'starts_at',
        'ends_at',
        'cancelled_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => RestaurantSubscriptionStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (RestaurantSubscription $subscription): void {
            if (! $subscription->status->occupiesRestaurantSlot()) {
                return;
            }

            $conflict = static::query()
                ->where('restaurant_id', $subscription->restaurant_id)
                ->whereIn('status', [
                    RestaurantSubscriptionStatus::Trial->value,
                    RestaurantSubscriptionStatus::Active->value,
                ])
                ->when(
                    $subscription->exists,
                    fn ($q) => $q->whereKeyNot($subscription->getKey()),
                )
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'data.restaurant_id' => [__('This restaurant already has an active or trial subscription.')],
                ]);
            }
        });
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(RestaurantInvoice::class);
    }
}
