<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\BranchStatus;
use App\Enums\RestaurantStatus;
use App\Enums\TableStatus;
use App\Models\Booking;
use App\Models\BookingNotification;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\SeatingArea;
use App\Models\User;
use App\Services\Bookings\BookingTransitionService;
use Database\Seeders\RolesAndTestUsersSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileBookingsApiTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
    }

    private function makeActiveRestaurantGraph(): array
    {
        $restaurant = Restaurant::create([
            'name' => 'R',
            'slug' => 'r',
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

    private function makeCustomer(string $email, string $phone): User
    {
        $user = User::create([
            'name' => '',
            'phone' => $phone,
            'email' => $email,
            'password' => Hash::make('x'),
        ]);
        $user->syncRoles(['customer']);

        return $user;
    }

    public function test_customer_can_create_pending_booking(): void
    {
        $data = $this->makeActiveRestaurantGraph();
        $customer = $this->makeCustomer('c1@mobile.eventaat.test', '+15550010001');
        $token = $customer->createToken('mobile')->plainTextToken;

        $startsAt = Carbon::now()->addHours(2)->toISOString();

        $resp = $this->withToken($token)->postJson('/api/mobile/bookings', [
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'seating_area_id' => $data['area']->id,
            'restaurant_table_id' => $data['table']->id,
            'starts_at' => $startsAt,
            'party_size' => 2,
            'customer_note' => 'Hi',
        ])->assertStatus(201);

        $resp->assertJsonPath('booking.status', 'pending');
        $resp->assertJsonPath('booking.party_size', 2);

        $this->assertDatabaseHas('bookings', [
            'customer_id' => $customer->id,
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'status' => 'pending',
        ]);

        $bookingId = $resp->json('booking.id');
        $this->assertNotNull($bookingId);
        $this->assertDatabaseHas('booking_notifications', [
            'booking_id' => $bookingId,
            'event' => BookingNotification::EVENT_BOOKING_CREATED,
            'status' => 'pending',
            'channel' => 'internal',
        ]);
    }

    public function test_customer_cannot_exceed_table_capacity(): void
    {
        $data = $this->makeActiveRestaurantGraph();
        $data['table']->update(['capacity' => 2]);

        $customer = $this->makeCustomer('cap@mobile.eventaat.test', '+15550010008');
        $token = $customer->createToken('mobile')->plainTextToken;

        $this->withToken($token)->postJson('/api/mobile/bookings', [
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'restaurant_table_id' => $data['table']->id,
            'starts_at' => Carbon::now()->addHours(2)->toISOString(),
            'party_size' => 3,
        ])->assertStatus(422)->assertJsonValidationErrors(['party_size']);
    }

    public function test_customer_cannot_book_inactive_restaurant(): void
    {
        $data = $this->makeActiveRestaurantGraph();
        $data['restaurant']->update(['status' => RestaurantStatus::Inactive]);

        $customer = $this->makeCustomer('c2@mobile.eventaat.test', '+15550010002');
        $token = $customer->createToken('mobile')->plainTextToken;

        $this->withToken($token)->postJson('/api/mobile/bookings', [
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'starts_at' => Carbon::now()->addHours(2)->toISOString(),
            'party_size' => 2,
        ])->assertStatus(422)->assertJsonValidationErrors(['restaurant_id']);
    }

    public function test_customer_cannot_book_inactive_branch(): void
    {
        $data = $this->makeActiveRestaurantGraph();
        $data['branch']->update(['status' => BranchStatus::Inactive]);

        $customer = $this->makeCustomer('c3@mobile.eventaat.test', '+15550010003');
        $token = $customer->createToken('mobile')->plainTextToken;

        $this->withToken($token)->postJson('/api/mobile/bookings', [
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'starts_at' => Carbon::now()->addHours(2)->toISOString(),
            'party_size' => 2,
        ])->assertStatus(422)->assertJsonValidationErrors(['branch_id']);
    }

    public function test_customer_sees_only_their_own_bookings_and_can_view_detail(): void
    {
        $data = $this->makeActiveRestaurantGraph();
        $c1 = $this->makeCustomer('c4@mobile.eventaat.test', '+15550010004');
        $c2 = $this->makeCustomer('c5@mobile.eventaat.test', '+15550010005');

        $b1 = Booking::create([
            'customer_id' => $c1->id,
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'starts_at' => Carbon::now()->addHours(4),
            'party_size' => 2,
            'status' => 'pending',
        ]);
        $b2 = Booking::create([
            'customer_id' => $c2->id,
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'starts_at' => Carbon::now()->addHours(5),
            'party_size' => 2,
            'status' => 'pending',
        ]);

        $token1 = $c1->createToken('mobile')->plainTextToken;

        $list = $this->withToken($token1)->getJson('/api/mobile/bookings')->assertOk();
        $list->assertJsonFragment(['id' => $b1->id]);
        $list->assertJsonMissing(['id' => $b2->id]);

        $this->withToken($token1)->getJson("/api/mobile/bookings/{$b1->id}")
            ->assertOk()
            ->assertJsonPath('id', $b1->id);

        $this->withToken($token1)->getJson("/api/mobile/bookings/{$b2->id}")
            ->assertStatus(404);
    }

    public function test_customer_can_cancel_own_pending_or_accepted_booking(): void
    {
        $data = $this->makeActiveRestaurantGraph();
        $c1 = $this->makeCustomer('c6@mobile.eventaat.test', '+15550010006');

        $pending = Booking::create([
            'customer_id' => $c1->id,
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'starts_at' => Carbon::now()->addHours(6),
            'party_size' => 2,
            'status' => 'pending',
        ]);

        $accepted = Booking::create([
            'customer_id' => $c1->id,
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'starts_at' => Carbon::now()->addHours(7),
            'party_size' => 2,
            'status' => 'accepted',
            'accepted_at' => Carbon::now(),
        ]);

        $token1 = $c1->createToken('mobile')->plainTextToken;

        $this->withToken($token1)->postJson("/api/mobile/bookings/{$pending->id}/cancel")
            ->assertOk()
            ->assertJsonPath('booking.status', 'cancelled');

        $this->assertDatabaseHas('bookings', [
            'id' => $pending->id,
            'status' => 'cancelled',
        ]);

        $this->withToken($token1)->postJson("/api/mobile/bookings/{$accepted->id}/cancel")
            ->assertOk()
            ->assertJsonPath('booking.status', 'cancelled');
    }

    public function test_customer_cannot_cancel_another_customers_booking_and_gets_404(): void
    {
        $data = $this->makeActiveRestaurantGraph();
        $c1 = $this->makeCustomer('c8@mobile.eventaat.test', '+15550010008');
        $c2 = $this->makeCustomer('c9@mobile.eventaat.test', '+15550010009');

        $booking = Booking::create([
            'customer_id' => $c1->id,
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'starts_at' => Carbon::now()->addHours(8),
            'party_size' => 2,
            'status' => 'pending',
        ]);

        $token2 = $c2->createToken('mobile')->plainTextToken;

        $this->withToken($token2)->postJson("/api/mobile/bookings/{$booking->id}/cancel")
            ->assertStatus(404);
    }

    public function test_booking_conflict_prevention_rejects_double_booking_same_table_and_time(): void
    {
        $data = $this->makeActiveRestaurantGraph();
        $customer = $this->makeCustomer('conflict@mobile.eventaat.test', '+15550010010');
        $token = $customer->createToken('mobile')->plainTextToken;

        $startsAt = Carbon::now()->addHours(10)->toISOString();

        $this->withToken($token)->postJson('/api/mobile/bookings', [
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'restaurant_table_id' => $data['table']->id,
            'starts_at' => $startsAt,
            'party_size' => 2,
        ])->assertStatus(201);

        $this->withToken($token)->postJson('/api/mobile/bookings', [
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'restaurant_table_id' => $data['table']->id,
            'starts_at' => $startsAt,
            'party_size' => 2,
        ])->assertStatus(422)->assertJsonValidationErrors(['restaurant_table_id']);
    }

    public function test_cancelled_booking_does_not_block_new_booking_for_same_table_and_time(): void
    {
        $data = $this->makeActiveRestaurantGraph();
        $customer = $this->makeCustomer('conflict2@mobile.eventaat.test', '+15550010011');
        $token = $customer->createToken('mobile')->plainTextToken;

        $startsAt = Carbon::now()->addHours(11);

        Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'restaurant_table_id' => $data['table']->id,
            'starts_at' => $startsAt,
            'party_size' => 2,
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => Carbon::now(),
        ]);

        $this->withToken($token)->postJson('/api/mobile/bookings', [
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'restaurant_table_id' => $data['table']->id,
            'starts_at' => $startsAt->toISOString(),
            'party_size' => 2,
        ])->assertStatus(201);
    }

    public function test_mobile_booking_api_returns_expanded_status_values_and_timestamps(): void
    {
        $data = $this->makeActiveRestaurantGraph();
        $customer = $this->makeCustomer('phase6@mobile.eventaat.test', '+15550010111');
        $token = $customer->createToken('mobile')->plainTextToken;

        $booking = Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'seating_area_id' => $data['area']->id,
            'restaurant_table_id' => $data['table']->id,
            'starts_at' => Carbon::now()->addHours(6),
            'party_size' => 2,
            'status' => BookingStatus::Accepted,
            'accepted_at' => Carbon::now(),
        ]);

        app(BookingTransitionService::class)->arrive($booking, Carbon::parse('2026-01-01 11:00:00'));
        $booking->refresh();

        $detail = $this->withToken($token)->getJson("/api/mobile/bookings/{$booking->id}")
            ->assertOk()
            ->assertJsonPath('status', 'arrived');

        $detail->assertJsonPath('arrived_at', '2026-01-01T11:00:00.000000Z');
        $detail->assertJsonStructure([
            'accepted_at',
            'rejected_at',
            'cancelled_at',
            'arrived_at',
            'seated_at',
            'completed_at',
            'no_show_at',
        ]);

        $this->withToken($token)->getJson('/api/mobile/bookings?status=arrived')
            ->assertOk()
            ->assertJsonFragment(['status' => 'arrived']);
    }
}
