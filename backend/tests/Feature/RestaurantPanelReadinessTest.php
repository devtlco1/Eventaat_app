<?php

namespace Tests\Feature;

use App\Enums\BranchStatus;
use App\Enums\RestaurantStaffRole;
use App\Enums\RestaurantStatus;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantStaffAssignment;
use App\Models\User;
use Database\Seeders\RolesAndTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 8J lightweight smoke checks for the restaurant Filament panel.
 * Panel vs customer/platform separation lives in {@see PanelAccessTest} and {@see UserAuthorizationHelpersTest}.
 */
class RestaurantPanelReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
    }

    /**
     * @return array{restaurant: Restaurant, branch: Branch}
     */
    private function seedRestaurantWithAssignments(): array
    {
        $restaurant = Restaurant::create([
            'name' => 'Audit Restaurant',
            'slug' => 'audit-restaurant',
            'status' => RestaurantStatus::Active,
        ]);

        $branch = Branch::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Main',
            'code' => 'main',
            'status' => BranchStatus::Active,
        ]);

        $owner = User::where('email', 'restaurant_owner@eventaat.test')->firstOrFail();
        RestaurantStaffAssignment::create([
            'user_id' => $owner->id,
            'restaurant_id' => $restaurant->id,
            'branch_id' => null,
            'role' => RestaurantStaffRole::RestaurantOwner,
            'status' => 'active',
        ]);

        $manager = User::where('email', 'branch_manager@eventaat.test')->firstOrFail();
        RestaurantStaffAssignment::create([
            'user_id' => $manager->id,
            'restaurant_id' => $restaurant->id,
            'branch_id' => $branch->id,
            'role' => RestaurantStaffRole::BranchManager,
            'status' => 'active',
        ]);

        $host = User::where('email', 'restaurant_host@eventaat.test')->firstOrFail();
        RestaurantStaffAssignment::create([
            'user_id' => $host->id,
            'restaurant_id' => $restaurant->id,
            'branch_id' => $branch->id,
            'role' => RestaurantStaffRole::RestaurantHost,
            'status' => 'active',
        ]);

        return compact('restaurant', 'branch');
    }

    /**
     * @return list<string>
     */
    private function restaurantResourceIndexPaths(): array
    {
        return [
            '/restaurant/bookings',
            '/restaurant/restaurant-menus',
            '/restaurant/restaurant-events',
            '/restaurant/restaurant-offers',
            '/restaurant/restaurant-stories',
            '/restaurant/restaurant-reviews',
            '/restaurant/support-tickets',
            '/restaurant/restaurants',
            '/restaurant/branches',
            '/restaurant/seating-areas',
            '/restaurant/restaurant-tables',
            '/restaurant/restaurant-staff-assignments',
        ];
    }

    private function assertRestaurantIndexesOk(User $user): void
    {
        $this->actingAs($user);

        foreach ($this->restaurantResourceIndexPaths() as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_restaurant_owner_can_smoke_load_assigned_restaurant_indexes(): void
    {
        $this->seedRestaurantWithAssignments();
        $owner = User::where('email', 'restaurant_owner@eventaat.test')->firstOrFail();
        $this->assertRestaurantIndexesOk($owner);
    }

    public function test_branch_manager_can_smoke_load_assigned_restaurant_indexes(): void
    {
        $this->seedRestaurantWithAssignments();
        $manager = User::where('email', 'branch_manager@eventaat.test')->firstOrFail();
        $this->assertRestaurantIndexesOk($manager);
    }

    public function test_restaurant_host_can_smoke_load_assigned_restaurant_indexes(): void
    {
        $this->seedRestaurantWithAssignments();
        $host = User::where('email', 'restaurant_host@eventaat.test')->firstOrFail();
        $this->assertRestaurantIndexesOk($host);
    }
}
