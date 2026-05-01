<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\BranchStatus;
use App\Enums\RestaurantStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantEvent;
use App\Models\User;
use Database\Seeders\RolesAndTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EventCapacitySummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
    }

    private function seedRestaurantBranch(string $slug): array
    {
        $restaurant = Restaurant::create([
            'name' => 'R',
            'slug' => $slug,
            'status' => RestaurantStatus::Active,
        ]);

        $branch = Branch::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'B',
            'code' => 'b',
            'status' => BranchStatus::Active,
        ]);

        return compact('restaurant', 'branch');
    }

    private function customer(): User
    {
        $u = User::create([
            'name' => 'Customer',
            'email' => 'cap-summary@eventaat.test',
            'phone' => '+15553330000',
            'password' => Hash::make('x'),
        ]);
        $u->syncRoles(['customer']);

        return $u;
    }

    public function test_active_reserved_seats_counts_only_consuming_statuses(): void
    {
        $g = $this->seedRestaurantBranch('cap-summary-1');
        $customer = $this->customer();

        $event = RestaurantEvent::create([
            'restaurant_id' => $g['restaurant']->id,
            'branch_id' => $g['branch']->id,
            'title' => 'E',
            'slug' => 'cap-summary-event-1',
            'starts_at' => Carbon::now()->addDays(1),
            'status' => RestaurantEvent::STATUS_PUBLISHED,
            'booking_mode' => RestaurantEvent::BOOKING_MODE_EVENT,
            'capacity' => 10,
        ]);

        // Consuming
        foreach ([BookingStatus::Pending, BookingStatus::Accepted, BookingStatus::Arrived, BookingStatus::Seated] as $status) {
            Booking::create([
                'customer_id' => $customer->id,
                'restaurant_id' => $g['restaurant']->id,
                'branch_id' => $g['branch']->id,
                'starts_at' => Carbon::now()->addHours(4),
                'party_size' => 2,
                'status' => $status,
                'restaurant_event_id' => $event->id,
            ]);
        }

        // Non-consuming
        foreach ([BookingStatus::Completed, BookingStatus::Cancelled, BookingStatus::Rejected, BookingStatus::NoShow] as $status) {
            Booking::create([
                'customer_id' => $customer->id,
                'restaurant_id' => $g['restaurant']->id,
                'branch_id' => $g['branch']->id,
                'starts_at' => Carbon::now()->addHours(5),
                'party_size' => 99,
                'status' => $status,
                'restaurant_event_id' => $event->id,
            ]);
        }

        $this->assertSame(8, $event->activeReservedSeats()); // 4 * 2
        $this->assertSame(2, $event->remainingSeats());
    }

    public function test_capacity_null_means_unlimited_and_remaining_is_null(): void
    {
        $g = $this->seedRestaurantBranch('cap-summary-2');
        $customer = $this->customer();

        $event = RestaurantEvent::create([
            'restaurant_id' => $g['restaurant']->id,
            'branch_id' => $g['branch']->id,
            'title' => 'E2',
            'slug' => 'cap-summary-event-2',
            'starts_at' => Carbon::now()->addDays(1),
            'status' => RestaurantEvent::STATUS_PUBLISHED,
            'booking_mode' => RestaurantEvent::BOOKING_MODE_EVENT,
            'capacity' => null,
        ]);

        Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $g['restaurant']->id,
            'branch_id' => $g['branch']->id,
            'starts_at' => Carbon::now()->addHours(4),
            'party_size' => 5,
            'status' => BookingStatus::Accepted,
            'restaurant_event_id' => $event->id,
        ]);

        $this->assertSame(5, $event->activeReservedSeats());
        $this->assertNull($event->remainingSeats());
    }
}

