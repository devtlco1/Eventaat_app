<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\BranchStatus;
use App\Enums\RestaurantStaffRole;
use App\Enums\RestaurantStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantStaffAssignment;
use App\Models\User;
use Database\Seeders\RolesAndTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BookingPanelAccessAndScopingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
    }

    private function seedRestaurantsAndBookings(): array
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

        $customer = User::create([
            'name' => '',
            'phone' => '+15558880001',
            'email' => 'booking_customer@eventaat.test',
            'password' => Hash::make('x'),
        ]);
        $customer->syncRoles(['customer']);

        $aBooking = Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $a->id,
            'branch_id' => $aBranch->id,
            'starts_at' => Carbon::now()->addHours(6),
            'party_size' => 2,
            'status' => BookingStatus::Pending,
        ]);

        $bBooking = Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $b->id,
            'branch_id' => $bBranch->id,
            'starts_at' => Carbon::now()->addHours(7),
            'party_size' => 2,
            'status' => BookingStatus::Pending,
        ]);

        return compact('a', 'b', 'aBranch', 'bBranch', 'aBooking', 'bBooking');
    }

    private function assertDeniedOrNotFound(int $status): void
    {
        $this->assertTrue(in_array($status, [403, 404], true), "Expected 403/404, got {$status}");
    }

    public function test_restaurant_owner_sees_scoped_bookings_and_cannot_access_out_of_scope_booking_url(): void
    {
        $data = $this->seedRestaurantsAndBookings();

        $owner = User::where('email', 'restaurant_owner@eventaat.test')->firstOrFail();
        RestaurantStaffAssignment::create([
            'user_id' => $owner->id,
            'restaurant_id' => $data['a']->id,
            'branch_id' => null,
            'role' => RestaurantStaffRole::RestaurantOwner,
            'status' => 'active',
        ]);

        $this->actingAs($owner);

        $index = $this->get('/restaurant/bookings');
        $index->assertStatus(200);
        $index->assertSee('A Main');
        $index->assertDontSee('B Main');

        $this->get("/restaurant/bookings/{$data['bBooking']->id}/edit")
            ->tap(fn ($resp) => $this->assertDeniedOrNotFound($resp->getStatusCode()));
    }

    public function test_branch_scoped_staff_sees_only_branch_bookings(): void
    {
        $data = $this->seedRestaurantsAndBookings();

        $manager = User::where('email', 'branch_manager@eventaat.test')->firstOrFail();
        RestaurantStaffAssignment::create([
            'user_id' => $manager->id,
            'restaurant_id' => $data['a']->id,
            'branch_id' => $data['aBranch']->id,
            'role' => RestaurantStaffRole::BranchManager,
            'status' => 'active',
        ]);

        $this->actingAs($manager);

        $index = $this->get('/restaurant/bookings');
        $index->assertStatus(200);
        $index->assertSee('A Main');
        $index->assertDontSee('B Main');
    }

    public function test_platform_can_access_all_bookings(): void
    {
        $data = $this->seedRestaurantsAndBookings();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        $this->actingAs($admin);

        $index = $this->get('/platform/bookings');
        $index->assertStatus(200);
        $index->assertSee('Restaurant A');
        $index->assertSee('Restaurant B');
    }
}

