<?php

namespace Tests\Feature;

use App\Enums\BranchStatus;
use App\Enums\RestaurantStatus;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileRestaurantDiscoveryApiTest extends TestCase
{
    use DatabaseMigrations;

    private function authCustomer(): string
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::where('email', 'customer@eventaat.test')->firstOrFail();
        return $user->createToken('mobile')->plainTextToken;
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/mobile/restaurants')->assertStatus(401);
        $this->getJson('/api/mobile/restaurants/demo-restaurant-a')->assertStatus(401);
    }

    public function test_non_customer_token_is_rejected(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        $token = $admin->createToken('mobile')->plainTextToken;

        $this->withToken($token)->getJson('/api/mobile/restaurants')->assertStatus(403);
    }

    public function test_customer_can_list_active_restaurants_and_inactive_hidden(): void
    {
        $token = $this->authCustomer();

        // Add an inactive restaurant which must be hidden.
        Restaurant::create([
            'name' => 'Hidden Restaurant',
            'slug' => 'hidden-restaurant',
            'status' => RestaurantStatus::Inactive,
        ]);

        $resp = $this->withToken($token)->getJson('/api/mobile/restaurants');
        $resp->assertOk();
        $resp->assertJsonStructure([
            'data',
            'links',
            'meta',
        ]);

        $resp->assertJsonFragment(['slug' => 'demo-restaurant-a']);
        $resp->assertJsonMissing(['slug' => 'hidden-restaurant']);
    }

    public function test_search_by_name_works(): void
    {
        $token = $this->authCustomer();

        $resp = $this->withToken($token)->getJson('/api/mobile/restaurants?q=Demo Restaurant A');
        $resp->assertOk();
        $resp->assertJsonFragment(['slug' => 'demo-restaurant-a']);
        $resp->assertJsonMissing(['slug' => 'demo-restaurant-b']);
    }

    public function test_pagination_and_per_page_is_bounded(): void
    {
        $token = $this->authCustomer();

        $resp = $this->withToken($token)->getJson('/api/mobile/restaurants?per_page=1000');
        $resp->assertOk();
        $this->assertLessThanOrEqual(50, (int) ($resp->json('meta.per_page') ?? 0));
    }

    public function test_customer_can_view_active_restaurant_detail_with_nested_structure(): void
    {
        $token = $this->authCustomer();

        $resp = $this->withToken($token)->getJson('/api/mobile/restaurants/demo-restaurant-a');
        $resp->assertOk();
        $resp->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'slug',
                'branches' => [
                    '*' => [
                        'id',
                        'name',
                        'code',
                        'seating_areas' => [
                            '*' => [
                                'id',
                                'name',
                                'type',
                                'tables' => [
                                    '*' => [
                                        'id',
                                        'label',
                                        'capacity',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_inactive_restaurant_detail_returns_404(): void
    {
        $token = $this->authCustomer();

        $inactive = Restaurant::create([
            'name' => 'Hidden Restaurant',
            'slug' => 'hidden-restaurant',
            'status' => RestaurantStatus::Inactive,
        ]);

        // Even if it has branches, it must be hidden.
        Branch::create([
            'restaurant_id' => $inactive->id,
            'name' => 'Hidden Branch',
            'code' => 'main',
            'status' => BranchStatus::Active,
        ]);

        $this->withToken($token)->getJson('/api/mobile/restaurants/hidden-restaurant')->assertStatus(404);
    }

    public function test_bookings_endpoint_does_not_exist_in_phase_4a(): void
    {
        $token = $this->authCustomer();
        $this->withToken($token)->getJson('/api/mobile/bookings')->assertStatus(404);
    }
}

