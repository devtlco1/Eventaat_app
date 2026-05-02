<?php

namespace Database\Seeders;

use App\Enums\SubscriptionBillingInterval;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlansSeeder extends Seeder
{
    /**
     * Demo catalog plans (Phase subscription foundation — not billing-connected).
     *
     * Idempotent: keyed by slug via updateOrCreate.
     */
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'basic',
                'name' => 'Basic',
                'description' => 'Starter tier placeholder — adjust pricing when billing ships.',
                'price_amount' => 99000,
                'currency' => 'IQD',
                'billing_interval' => SubscriptionBillingInterval::Monthly,
                'is_active' => true,
                'display_order' => 10,
            ],
            [
                'slug' => 'pro',
                'name' => 'Pro',
                'description' => 'Growth tier placeholder.',
                'price_amount' => 249000,
                'currency' => 'IQD',
                'billing_interval' => SubscriptionBillingInterval::Monthly,
                'is_active' => true,
                'display_order' => 20,
            ],
            [
                'slug' => 'enterprise',
                'name' => 'Enterprise',
                'description' => 'Large footprint placeholder — yearly billing example.',
                'price_amount' => 2499000,
                'currency' => 'IQD',
                'billing_interval' => SubscriptionBillingInterval::Yearly,
                'is_active' => true,
                'display_order' => 30,
            ],
        ];

        foreach ($plans as $row) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'price_amount' => $row['price_amount'],
                    'currency' => $row['currency'],
                    'billing_interval' => $row['billing_interval'],
                    'is_active' => $row['is_active'],
                    'display_order' => $row['display_order'],
                ],
            );
        }
    }
}
