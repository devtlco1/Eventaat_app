<?php

namespace Tests\Unit;

use App\Enums\RestaurantSubscriptionStatus;
use App\Enums\SubscriptionBillingInterval;
use PHPUnit\Framework\TestCase;

class SubscriptionEnumsTest extends TestCase
{
    public function test_subscription_billing_interval_labels(): void
    {
        $this->assertSame('monthly', SubscriptionBillingInterval::Monthly->value);
        $this->assertSame('Monthly', SubscriptionBillingInterval::Monthly->label());
        $this->assertSame('Yearly', SubscriptionBillingInterval::Yearly->label());
    }

    public function test_restaurant_subscription_status_labels_and_slot_rule(): void
    {
        $this->assertSame('past_due', RestaurantSubscriptionStatus::PastDue->value);
        $this->assertSame('Past due', RestaurantSubscriptionStatus::PastDue->label());

        $this->assertTrue(RestaurantSubscriptionStatus::Trial->occupiesRestaurantSlot());
        $this->assertTrue(RestaurantSubscriptionStatus::Active->occupiesRestaurantSlot());
        $this->assertFalse(RestaurantSubscriptionStatus::Expired->occupiesRestaurantSlot());
    }
}
