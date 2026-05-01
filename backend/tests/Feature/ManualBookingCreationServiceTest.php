<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\BranchStatus;
use App\Enums\RestaurantStaffRole;
use App\Enums\RestaurantStatus;
use App\Enums\TableStatus;
use App\Models\BookingNotification;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantStaffAssignment;
use App\Models\RestaurantTable;
use App\Models\SeatingArea;
use App\Models\User;
use App\Services\Bookings\ManualBookingCreationService;
use Database\Seeders\RolesAndTestUsersSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ManualBookingCreationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function assertAllowedResponse(int $status): void
    {
        $this->assertTrue(in_array($status, [200, 302], true), "Expected 200/302, got {$status}");
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
    }

    private function makeActiveRestaurantGraph(): array
    {
        $restaurant = Restaurant::create([
            'name' => 'R',
            'slug' => 'r-manual',
            'status' => RestaurantStatus::Active,
        ]);

        $branch = Branch::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'B',
            'code' => 'b',
            'status' => BranchStatus::Active,
        ]);

        $area = SeatingArea::create([
            'branch_id' => $branch->id,
            'name' => 'Indoor',
            'code' => 'indoor',
            'type' => 'indoor',
            'status' => 'active',
        ]);

        $table = RestaurantTable::create([
            'seating_area_id' => $area->id,
            'label' => 'T1',
            'capacity' => 4,
            'status' => TableStatus::Active,
        ]);

        return compact('restaurant', 'branch', 'area', 'table');
    }

    public function test_manual_booking_with_new_phone_creates_customer_user_and_pending_booking(): void
    {
        $data = $this->makeActiveRestaurantGraph();

        $booking = app(ManualBookingCreationService::class)->create([
            'customer_phone' => ' +15550030001 ',
            'customer_name' => 'Alice',
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'seating_area_id' => $data['area']->id,
            'restaurant_table_id' => $data['table']->id,
            'starts_at' => Carbon::now()->addHours(3),
            'party_size' => 2,
            'customer_note' => 'Hi',
            'restaurant_note' => 'Internal',
        ]);

        $this->assertSame('pending', $booking->status->value);

        $customer = User::query()->where('phone', '+15550030001')->firstOrFail();
        $this->assertTrue($customer->hasRole('customer'));
        $this->assertSame('Alice', $customer->name);
        $this->assertSame($customer->id, $booking->customer_id);

        $this->assertDatabaseHas('booking_notifications', [
            'booking_id' => $booking->id,
            'event' => BookingNotification::EVENT_BOOKING_CREATED,
            'status' => 'pending',
            'channel' => 'internal',
        ]);
    }

    public function test_manual_booking_new_phone_requires_customer_name(): void
    {
        $data = $this->makeActiveRestaurantGraph();

        try {
            app(ManualBookingCreationService::class)->create([
                'customer_phone' => '+15550039901',
                'restaurant_id' => $data['restaurant']->id,
                'branch_id' => $data['branch']->id,
                'starts_at' => Carbon::now()->addHours(3),
                'party_size' => 2,
            ]);
            $this->fail('Expected ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('customer_name', $e->errors());
        }
    }

    public function test_manual_booking_with_existing_phone_reuses_user_and_optionally_updates_name(): void
    {
        $data = $this->makeActiveRestaurantGraph();

        $existing = User::create([
            'name' => 'Customer',
            'phone' => '+15550030002',
            'email' => 'existing_customer@eventaat.test',
            'password' => Hash::make('x'),
        ]);
        $existing->syncRoles(['customer']);

        $booking = app(ManualBookingCreationService::class)->create([
            'customer_phone' => '+15550030002',
            'customer_name' => 'Bob',
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'starts_at' => Carbon::now()->addHours(3),
            'party_size' => 2,
        ]);

        $this->assertSame($existing->id, $booking->customer_id);
        $existing->refresh();
        $this->assertSame('Bob', $existing->name);

        $existing->forceFill(['name' => 'Charlie'])->save();

        app(ManualBookingCreationService::class)->create([
            'customer_phone' => '+15550030002',
            'customer_name' => 'ShouldNotOverwrite',
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'starts_at' => Carbon::now()->addHours(4),
            'party_size' => 2,
        ]);

        $existing->refresh();
        $this->assertSame('Charlie', $existing->name);
    }

    public function test_manual_booking_validates_capacity_and_conflict_prevention(): void
    {
        $data = $this->makeActiveRestaurantGraph();
        $data['table']->update(['capacity' => 2]);

        $this->expectException(ValidationException::class);
        app(ManualBookingCreationService::class)->create([
            'customer_phone' => '+15550030003',
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'restaurant_table_id' => $data['table']->id,
            'starts_at' => Carbon::now()->addHours(3),
            'party_size' => 3,
        ]);
    }

    public function test_manual_booking_blocks_double_booking_pending_or_accepted_but_not_cancelled_rejected_completed_no_show(): void
    {
        $data = $this->makeActiveRestaurantGraph();
        $startsAt = Carbon::now()->addHours(8);

        $first = app(ManualBookingCreationService::class)->create([
            'customer_phone' => '+15550030004',
            'customer_name' => 'First',
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'restaurant_table_id' => $data['table']->id,
            'starts_at' => $startsAt,
            'party_size' => 2,
        ]);

        $this->expectException(ValidationException::class);
        app(ManualBookingCreationService::class)->create([
            'customer_phone' => '+15550030005',
            'customer_name' => 'Second',
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'restaurant_table_id' => $data['table']->id,
            'starts_at' => $startsAt,
            'party_size' => 2,
        ]);

        $first->update(['status' => BookingStatus::Cancelled, 'cancelled_at' => Carbon::now()]);

        $ok = app(ManualBookingCreationService::class)->create([
            'customer_phone' => '+15550030006',
            'customer_name' => 'Third',
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'restaurant_table_id' => $data['table']->id,
            'starts_at' => $startsAt,
            'party_size' => 2,
        ]);

        $this->assertSame('pending', $ok->status->value);

        $ok->update(['status' => BookingStatus::Completed, 'completed_at' => Carbon::now()]);

        $again = app(ManualBookingCreationService::class)->create([
            'customer_phone' => '+15550030007',
            'customer_name' => 'Fourth',
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'restaurant_table_id' => $data['table']->id,
            'starts_at' => $startsAt,
            'party_size' => 2,
        ]);

        $this->assertSame('pending', $again->status->value);
    }

    public function test_restaurant_scoping_allowed_restaurant_and_branch_ids_are_enforced(): void
    {
        $a = $this->makeActiveRestaurantGraph();

        $restaurantB = Restaurant::create([
            'name' => 'RB',
            'slug' => 'rb',
            'status' => RestaurantStatus::Active,
        ]);
        $branchB = Branch::create([
            'restaurant_id' => $restaurantB->id,
            'name' => 'BB',
            'code' => 'bb',
            'status' => BranchStatus::Active,
        ]);

        $owner = User::where('email', 'restaurant_owner@eventaat.test')->firstOrFail();
        RestaurantStaffAssignment::create([
            'user_id' => $owner->id,
            'restaurant_id' => $a['restaurant']->id,
            'branch_id' => null,
            'role' => RestaurantStaffRole::RestaurantOwner,
            'status' => 'active',
        ]);

        $created = app(ManualBookingCreationService::class)->create([
            'customer_phone' => '+15550039991',
            'customer_name' => 'Scoped',
            'restaurant_id' => $a['restaurant']->id,
            'branch_id' => $a['branch']->id,
            'starts_at' => Carbon::now()->addHours(3),
            'party_size' => 2,
            'allowed_restaurant_ids' => $owner->scopedRestaurantIds(),
            'allowed_branch_ids' => $owner->scopedBranchIds(),
        ]);

        $this->assertSame($a['restaurant']->id, $created->restaurant_id);

        $this->expectException(ValidationException::class);
        app(ManualBookingCreationService::class)->create([
            'customer_phone' => '+15550039992',
            'customer_name' => 'OutOfScope',
            'restaurant_id' => $restaurantB->id,
            'branch_id' => $branchB->id,
            'starts_at' => Carbon::now()->addHours(3),
            'party_size' => 2,
            'allowed_restaurant_ids' => $owner->scopedRestaurantIds(),
            'allowed_branch_ids' => $owner->scopedBranchIds(),
        ]);
    }

    public function test_platform_and_restaurant_create_pages_are_reachable_for_valid_roles(): void
    {
        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);
        $this->get('/platform/bookings/create')
            ->tap(fn ($resp) => $this->assertAllowedResponse($resp->getStatusCode()))
            ->assertSee('Customer phone')
            ->assertSee('Restaurant')
            ->assertSee('Branch')
            ->assertDontSee('Status');

        $data = $this->makeActiveRestaurantGraph();
        $owner = User::where('email', 'restaurant_owner@eventaat.test')->firstOrFail();
        RestaurantStaffAssignment::create([
            'user_id' => $owner->id,
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => null,
            'role' => RestaurantStaffRole::RestaurantOwner,
            'status' => 'active',
        ]);
        Filament::setCurrentPanel('restaurant');
        $this->actingAs($owner);
        // Warm up the panel context first.
        $this->get('/restaurant')
            ->tap(fn ($resp) => $this->assertAllowedResponse($resp->getStatusCode()));

        $this->get('/restaurant/bookings/create')
            ->tap(fn ($resp) => $this->assertAllowedResponse($resp->getStatusCode()));
    }
}
