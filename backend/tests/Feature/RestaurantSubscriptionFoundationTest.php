<?php

namespace Tests\Feature;

use App\Enums\RestaurantStatus;
use App\Enums\RestaurantSubscriptionStatus;
use App\Filament\Platform\Resources\RestaurantSubscriptions\Pages\CreateRestaurantSubscription as PlatformCreateRestaurantSubscription;
use App\Filament\Platform\Resources\RestaurantSubscriptions\Pages\EditRestaurantSubscription as PlatformEditRestaurantSubscription;
use App\Models\Restaurant;
use App\Models\RestaurantSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\RolesAndTestUsersSeeder;
use Database\Seeders\SubscriptionPlansSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class RestaurantSubscriptionFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
        $this->seed(SubscriptionPlansSeeder::class);
    }

    private function makeRestaurant(string $slug): Restaurant
    {
        static $n = 0;
        $n++;

        return Restaurant::create([
            'name' => 'Sub Restaurant '.$n,
            'slug' => $slug.'-'.$n,
            'status' => RestaurantStatus::Active,
        ]);
    }

    public function test_platform_super_admin_can_access_subscription_plan_and_subscription_indexes(): void
    {
        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        $this->get('/platform/subscription-plans')->assertOk();
        $this->get('/platform/restaurant-subscriptions')->assertOk();
    }

    public function test_restaurant_owner_cannot_access_platform_subscription_resources(): void
    {
        $owner = User::where('email', 'restaurant_owner@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($owner);

        $this->get('/platform/subscription-plans')->assertForbidden();
        $this->get('/platform/restaurant-subscriptions')->assertForbidden();
    }

    public function test_platform_can_create_and_edit_restaurant_subscription_via_filament(): void
    {
        $restaurant = $this->makeRestaurant('filament-sub');
        $plan = SubscriptionPlan::query()->where('slug', 'basic')->firstOrFail();

        $admin = User::where('email', 'operations_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformCreateRestaurantSubscription::class)
            ->set('data.restaurant_id', $restaurant->id)
            ->set('data.subscription_plan_id', $plan->id)
            ->set('data.status', RestaurantSubscriptionStatus::Trial->value)
            ->set('data.notes', 'Started trial')
            ->call('create')
            ->assertHasNoErrors();

        $sub = RestaurantSubscription::query()->where('restaurant_id', $restaurant->id)->firstOrFail();
        $this->assertSame(RestaurantSubscriptionStatus::Trial, $sub->status);
        $this->assertSame($plan->id, $sub->subscription_plan_id);

        Livewire::test(PlatformEditRestaurantSubscription::class, ['record' => $sub->getKey()])
            ->set('data.notes', 'Updated notes')
            ->call('save')
            ->assertHasNoErrors();

        $sub->refresh();
        $this->assertSame('Updated notes', $sub->notes);
    }

    public function test_second_active_subscription_for_same_restaurant_fails_validation(): void
    {
        $restaurant = $this->makeRestaurant('dup-sub');

        RestaurantSubscription::create([
            'restaurant_id' => $restaurant->id,
            'subscription_plan_id' => null,
            'status' => RestaurantSubscriptionStatus::Active,
            'notes' => null,
        ]);

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformCreateRestaurantSubscription::class)
            ->set('data.restaurant_id', $restaurant->id)
            ->set('data.subscription_plan_id', null)
            ->set('data.status', RestaurantSubscriptionStatus::Trial->value)
            ->call('create')
            ->assertHasErrors(['data.restaurant_id']);
    }

    public function test_model_save_duplicate_slot_throws_validation_exception(): void
    {
        $restaurant = $this->makeRestaurant('model-dup');

        RestaurantSubscription::create([
            'restaurant_id' => $restaurant->id,
            'subscription_plan_id' => null,
            'status' => RestaurantSubscriptionStatus::Active,
            'notes' => null,
        ]);

        $this->expectException(ValidationException::class);

        RestaurantSubscription::create([
            'restaurant_id' => $restaurant->id,
            'subscription_plan_id' => null,
            'status' => RestaurantSubscriptionStatus::Trial,
            'notes' => null,
        ]);
    }
}
