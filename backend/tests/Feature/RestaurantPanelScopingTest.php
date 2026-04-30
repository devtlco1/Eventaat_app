<?php

namespace Tests\Feature;

use App\Enums\BranchStatus;
use App\Enums\RestaurantStaffRole;
use App\Enums\RestaurantStatus;
use App\Enums\SeatingAreaType;
use App\Enums\TableStatus;
use App\Models\Branch;
use App\Models\BranchAvailabilityRule;
use App\Models\Restaurant;
use App\Models\RestaurantStaffAssignment;
use App\Models\RestaurantTable;
use App\Models\SeatingArea;
use App\Models\User;
use Database\Seeders\RolesAndTestUsersSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantPanelScopingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
    }

    private function seedRestaurants(): array
    {
        $a = Restaurant::create([
            'name' => 'Restaurant A',
            'slug' => 'restaurant-a',
            'status' => RestaurantStatus::Active,
        ]);

        $b = Restaurant::create([
            'name' => 'Restaurant B',
            'slug' => 'restaurant-b',
            'status' => RestaurantStatus::Active,
        ]);

        $aBranch = Branch::create([
            'restaurant_id' => $a->id,
            'name' => 'A Main',
            'code' => 'main',
            'status' => BranchStatus::Active,
        ]);

        $bBranch = Branch::create([
            'restaurant_id' => $b->id,
            'name' => 'B Main',
            'code' => 'main',
            'status' => BranchStatus::Active,
        ]);

        $aArea = SeatingArea::create([
            'branch_id' => $aBranch->id,
            'name' => 'A Indoor',
            'code' => 'indoor',
            'type' => SeatingAreaType::Indoor,
            'status' => 'active',
        ]);

        $bArea = SeatingArea::create([
            'branch_id' => $bBranch->id,
            'name' => 'B Indoor',
            'code' => 'indoor',
            'type' => SeatingAreaType::Indoor,
            'status' => 'active',
        ]);

        $aTable = RestaurantTable::create([
            'seating_area_id' => $aArea->id,
            'label' => 'T1',
            'capacity' => 2,
            'status' => TableStatus::Active,
        ]);

        $bTable = RestaurantTable::create([
            'seating_area_id' => $bArea->id,
            'label' => 'T1',
            'capacity' => 2,
            'status' => TableStatus::Active,
        ]);

        return compact('a', 'b', 'aBranch', 'bBranch', 'aArea', 'bArea', 'aTable', 'bTable');
    }

    private function denyOrNotFound(): array
    {
        return [403, 404];
    }

    private function assertDeniedOrNotFound(int $status): void
    {
        $this->assertTrue(
            in_array($status, $this->denyOrNotFound(), true),
            "Expected status 403 or 404, got {$status}.",
        );
    }

    public function test_owner_sees_only_assigned_restaurant_and_denies_direct_access(): void
    {
        $data = $this->seedRestaurants();

        $owner = User::where('email', 'restaurant_owner@eventaat.test')->firstOrFail();

        RestaurantStaffAssignment::create([
            'user_id' => $owner->id,
            'restaurant_id' => $data['a']->id,
            'branch_id' => null,
            'role' => RestaurantStaffRole::RestaurantOwner,
            'status' => 'active',
        ]);

        $this->actingAs($owner);

        $index = $this->get('/restaurant/restaurants');
        $index->assertStatus(200);
        $index->assertSee('Restaurant A');
        $index->assertDontSee('Restaurant B');

        $this->get("/restaurant/restaurants/{$data['b']->id}")
            ->tap(fn ($resp) => $this->assertDeniedOrNotFound($resp->getStatusCode()));
    }

    public function test_branch_manager_is_branch_scoped_and_direct_access_is_denied(): void
    {
        $data = $this->seedRestaurants();

        $manager = User::where('email', 'branch_manager@eventaat.test')->firstOrFail();

        RestaurantStaffAssignment::create([
            'user_id' => $manager->id,
            'restaurant_id' => $data['a']->id,
            'branch_id' => $data['aBranch']->id,
            'role' => RestaurantStaffRole::BranchManager,
            'status' => 'active',
        ]);

        $this->actingAs($manager);

        $branches = $this->get('/restaurant/branches');
        $branches->assertStatus(200);
        $branches->assertSee('A Main');
        $branches->assertDontSee('B Main');

        $this->get("/restaurant/branches/{$data['bBranch']->id}")
            ->tap(fn ($resp) => $this->assertDeniedOrNotFound($resp->getStatusCode()));

        $tables = $this->get('/restaurant/restaurant-tables');
        $tables->assertStatus(200);
        $tables->assertSee('T1');
    }

    public function test_unrelated_restaurant_user_cannot_see_other_restaurant_data(): void
    {
        $data = $this->seedRestaurants();

        $owner = User::where('email', 'restaurant_owner@eventaat.test')->firstOrFail();
        $otherOwner = User::factory()->create(['email' => 'other_owner@eventaat.test']);
        $otherOwner->assignRole('restaurant_owner');

        RestaurantStaffAssignment::create([
            'user_id' => $owner->id,
            'restaurant_id' => $data['a']->id,
            'branch_id' => null,
            'role' => RestaurantStaffRole::RestaurantOwner,
            'status' => 'active',
        ]);

        RestaurantStaffAssignment::create([
            'user_id' => $otherOwner->id,
            'restaurant_id' => $data['b']->id,
            'branch_id' => null,
            'role' => RestaurantStaffRole::RestaurantOwner,
            'status' => 'active',
        ]);

        $this->actingAs($otherOwner);

        $restaurants = $this->get('/restaurant/restaurants');
        $restaurants->assertStatus(200);
        $restaurants->assertSee('Restaurant B');
        $restaurants->assertDontSee('Restaurant A');

        $this->get("/restaurant/restaurants/{$data['a']->id}")
            ->tap(fn ($resp) => $this->assertDeniedOrNotFound($resp->getStatusCode()));
    }

    public function test_scoped_branch_view_shows_booking_availability_relation_tab(): void
    {
        $data = $this->seedRestaurants();

        BranchAvailabilityRule::create([
            'branch_id' => $data['aBranch']->id,
            'is_booking_enabled' => true,
            'booking_duration_minutes' => 90,
            'min_advance_minutes' => 60,
            'max_advance_days' => 30,
            'open_time' => '10:00:00',
            'close_time' => '23:00:00',
            'mon' => true,
            'tue' => true,
            'wed' => true,
            'thu' => true,
            'fri' => true,
            'sat' => true,
            'sun' => true,
            'notes' => null,
        ]);

        $manager = User::where('email', 'branch_manager@eventaat.test')->firstOrFail();

        RestaurantStaffAssignment::create([
            'user_id' => $manager->id,
            'restaurant_id' => $data['a']->id,
            'branch_id' => $data['aBranch']->id,
            'role' => RestaurantStaffRole::BranchManager,
            'status' => 'active',
        ]);

        Filament::setCurrentPanel('restaurant');
        $this->actingAs($manager);

        $this->get("/restaurant/branches/{$data['aBranch']->id}")
            ->assertOk()
            ->assertSee('Booking availability');
    }
}
