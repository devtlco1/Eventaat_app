<?php

namespace App\Models;

use App\Enums\RestaurantInvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class RestaurantInvoice extends Model
{
    protected $fillable = [
        'restaurant_id',
        'restaurant_subscription_id',
        'invoice_number',
        'status',
        'issue_date',
        'due_date',
        'paid_at',
        'subtotal_amount',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'currency',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => RestaurantInvoiceStatus::class,
            'issue_date' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'subtotal_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (RestaurantInvoice $invoice): void {
            $subtotal = max(0, (float) $invoice->subtotal_amount);
            $discount = max(0, (float) $invoice->discount_amount);
            $discount = min($discount, $subtotal);
            $tax = max(0, (float) $invoice->tax_amount);

            $invoice->discount_amount = round($discount, 2);
            $invoice->subtotal_amount = round($subtotal, 2);
            $invoice->tax_amount = round($tax, 2);
            $invoice->total_amount = round($subtotal - $discount + $tax, 2);

            if ($invoice->restaurant_subscription_id) {
                $sub = RestaurantSubscription::query()->find($invoice->restaurant_subscription_id);
                if (! $sub || (int) $sub->restaurant_id !== (int) $invoice->restaurant_id) {
                    throw ValidationException::withMessages([
                        'data.restaurant_subscription_id' => [__('The selected subscription does not belong to this restaurant.')],
                    ]);
                }
            }
        });
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function restaurantSubscription(): BelongsTo
    {
        return $this->belongsTo(RestaurantSubscription::class);
    }
}
