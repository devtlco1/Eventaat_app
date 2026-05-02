<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use Database\Seeders\SubscriptionPlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPlansSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_plans_seeder_is_idempotent(): void
    {
        $this->seed(SubscriptionPlansSeeder::class);
        $this->assertSame(3, SubscriptionPlan::query()->count());

        $this->seed(SubscriptionPlansSeeder::class);
        $this->assertSame(3, SubscriptionPlan::query()->count());

        $this->assertDatabaseHas('subscription_plans', ['slug' => 'basic', 'name' => 'Basic']);
        $this->assertDatabaseHas('subscription_plans', ['slug' => 'pro', 'name' => 'Pro']);
        $this->assertDatabaseHas('subscription_plans', ['slug' => 'enterprise', 'name' => 'Enterprise']);
    }
}
