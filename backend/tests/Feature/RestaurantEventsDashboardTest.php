<?php

namespace Tests\Feature;

use App\Enums\BranchStatus;
use App\Enums\RestaurantStaffRole;
use App\Enums\RestaurantStatus;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantEvent;
use App\Models\RestaurantStaffAssignment;
use App\Models\User;
use Database\Seeders\RolesAndTestUsersSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RestaurantEventsDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
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

    private function seedRestaurantsBranchesAndEvents(): array
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

        $aBranchEvent = RestaurantEvent::create([
            'restaurant_id' => $a->id,
            'branch_id' => $aBranch->id,
            'title' => 'A Branch Event',
            'slug' => 'a-branch-event',
            'starts_at' => Carbon::now()->addDays(3),
            'status' => RestaurantEvent::STATUS_PUBLISHED,
            'booking_mode' => RestaurantEvent::BOOKING_MODE_INFO_ONLY,
        ]);

        $aRestaurantWideEvent = RestaurantEvent::create([
            'restaurant_id' => $a->id,
            'branch_id' => null,
            'title' => 'A Restaurant Wide Event',
            'slug' => 'a-restaurant-wide-event',
            'starts_at' => Carbon::now()->addDays(5),
            'status' => RestaurantEvent::STATUS_DRAFT,
            'booking_mode' => RestaurantEvent::BOOKING_MODE_INFO_ONLY,
        ]);

        $bBranchEvent = RestaurantEvent::create([
            'restaurant_id' => $b->id,
            'branch_id' => $bBranch->id,
            'title' => 'B Branch Event',
            'slug' => 'b-branch-event',
            'starts_at' => Carbon::now()->addDays(6),
            'status' => RestaurantEvent::STATUS_DRAFT,
            'booking_mode' => RestaurantEvent::BOOKING_MODE_INFO_ONLY,
        ]);

        return compact('a', 'b', 'aBranch', 'bBranch', 'aBranchEvent', 'aRestaurantWideEvent', 'bBranchEvent');
    }

    public function test_platform_can_list_all_events(): void
    {
        $data = $this->seedRestaurantsBranchesAndEvents();
        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        $this->actingAs($admin);

        $resp = $this->get('/platform/restaurant-events');
        $resp->assertOk();
        $resp->assertSee($data['a']->name);
        $resp->assertSee($data['b']->name);
        $resp->assertSee('A Branch Event');
        $resp->assertSee('B Branch Event');
    }

    public function test_restaurant_owner_sees_only_assigned_restaurant_events_including_restaurant_wide(): void
    {
        $data = $this->seedRestaurantsBranchesAndEvents();

        $owner = User::where('email', 'restaurant_owner@eventaat.test')->firstOrFail();
        RestaurantStaffAssignment::create([
            'user_id' => $owner->id,
            'restaurant_id' => $data['a']->id,
            'branch_id' => null,
            'role' => RestaurantStaffRole::RestaurantOwner,
            'status' => 'active',
        ]);

        Filament::setCurrentPanel('restaurant');
        $this->actingAs($owner);

        $index = $this->get('/restaurant/restaurant-events');
        $index->assertOk();
        $index->assertSee('A Branch Event');
        $index->assertSee('A Restaurant Wide Event');
        $index->assertDontSee('B Branch Event');
    }

    public function test_branch_scoped_staff_sees_only_branch_scoped_events_and_cannot_access_restaurant_wide(): void
    {
        $data = $this->seedRestaurantsBranchesAndEvents();

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

        $index = $this->get('/restaurant/restaurant-events');
        $index->assertOk();
        $index->assertSee('A Branch Event');
        $index->assertDontSee('A Restaurant Wide Event');
        $index->assertDontSee('B Branch Event');

        $this->get("/restaurant/restaurant-events/{$data['aRestaurantWideEvent']->id}")
            ->tap(fn ($resp) => $this->assertDeniedOrNotFound($resp->getStatusCode()));
    }

    public function test_out_of_scope_direct_access_is_denied(): void
    {
        $data = $this->seedRestaurantsBranchesAndEvents();

        $owner = User::where('email', 'restaurant_owner@eventaat.test')->firstOrFail();
        RestaurantStaffAssignment::create([
            'user_id' => $owner->id,
            'restaurant_id' => $data['a']->id,
            'branch_id' => null,
            'role' => RestaurantStaffRole::RestaurantOwner,
            'status' => 'active',
        ]);

        Filament::setCurrentPanel('restaurant');
        $this->actingAs($owner);

        $this->get("/restaurant/restaurant-events/{$data['bBranchEvent']->id}")
            ->tap(fn ($resp) => $this->assertDeniedOrNotFound($resp->getStatusCode()));
    }
}

