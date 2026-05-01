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
use App\Services\Bookings\ManualBookingCreationService;
use Database\Seeders\RolesAndTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EventBookingLinkFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
    }

    private function seedRestaurantGraph(string $slug): array
    {
        $restaurant = Restaurant::create([
            'name' => 'R',
            'slug' => $slug,
            'status' => RestaurantStatus::Active,
        ]);

        $branchA = Branch::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'A',
            'code' => 'a',
            'status' => BranchStatus::Active,
        ]);

        $branchB = Branch::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'B',
            'code' => 'b',
            'status' => BranchStatus::Active,
        ]);

        return compact('restaurant', 'branchA', 'branchB');
    }

    private function makeCustomer(string $email, string $phone): User
    {
        $u = User::create([
            'name' => 'Customer',
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make('x'),
        ]);
        $u->syncRoles(['customer']);

        return $u;
    }

    public function test_normal_booking_without_event_still_works(): void
    {
        $g = $this->seedRestaurantGraph('evt-normal');

        $booking = app(ManualBookingCreationService::class)->create([
            'customer_phone' => '+15551110001',
            'customer_name' => 'A',
            'restaurant_id' => $g['restaurant']->id,
            'branch_id' => $g['branchA']->id,
            'starts_at' => Carbon::now()->addHours(3),
            'party_size' => 2,
        ]);

        $this->assertSame('pending', $booking->status->value);
        $this->assertNull($booking->restaurant_event_id);
    }

    public function test_booking_can_link_to_published_event_with_bookable_booking_mode(): void
    {
        $g = $this->seedRestaurantGraph('evt-link');

        $event = RestaurantEvent::create([
            'restaurant_id' => $g['restaurant']->id,
            'branch_id' => $g['branchA']->id,
            'title' => 'Live Music Night',
            'slug' => 'evt-link-live-music',
            'starts_at' => Carbon::now()->addDays(2),
            'status' => RestaurantEvent::STATUS_PUBLISHED,
            'booking_mode' => RestaurantEvent::BOOKING_MODE_EVENT,
        ]);

        $booking = app(ManualBookingCreationService::class)->create([
            'customer_phone' => '+15551110002',
            'customer_name' => 'B',
            'restaurant_id' => $g['restaurant']->id,
            'branch_id' => $g['branchA']->id,
            'restaurant_event_id' => $event->id,
            'starts_at' => Carbon::now()->addHours(4),
            'party_size' => 2,
        ]);

        $this->assertSame($event->id, $booking->restaurant_event_id);
    }

    public function test_info_only_event_cannot_be_used_for_bookings(): void
    {
        $g = $this->seedRestaurantGraph('evt-info-only');

        $event = RestaurantEvent::create([
            'restaurant_id' => $g['restaurant']->id,
            'branch_id' => $g['branchA']->id,
            'title' => 'Info Only',
            'slug' => 'evt-info-only',
            'starts_at' => Carbon::now()->addDays(2),
            'status' => RestaurantEvent::STATUS_PUBLISHED,
            'booking_mode' => RestaurantEvent::BOOKING_MODE_INFO_ONLY,
        ]);

        $this->expectException(ValidationException::class);
        app(ManualBookingCreationService::class)->create([
            'customer_phone' => '+15551110003',
            'customer_name' => 'C',
            'restaurant_id' => $g['restaurant']->id,
            'branch_id' => $g['branchA']->id,
            'restaurant_event_id' => $event->id,
            'starts_at' => Carbon::now()->addHours(5),
            'party_size' => 2,
        ]);
    }

    public function test_draft_cancelled_completed_events_cannot_be_used_for_bookings(): void
    {
        $g = $this->seedRestaurantGraph('evt-status-block');

        foreach ([RestaurantEvent::STATUS_DRAFT, RestaurantEvent::STATUS_CANCELLED, RestaurantEvent::STATUS_COMPLETED] as $status) {
            $event = RestaurantEvent::create([
                'restaurant_id' => $g['restaurant']->id,
                'branch_id' => $g['branchA']->id,
                'title' => "Event {$status}",
                'slug' => "evt-status-{$status}",
                'starts_at' => Carbon::now()->addDays(2),
                'status' => $status,
                'booking_mode' => RestaurantEvent::BOOKING_MODE_EVENT,
            ]);

            try {
                app(ManualBookingCreationService::class)->create([
                    'customer_phone' => '+1555111999'.rand(10, 99),
                    'customer_name' => 'X',
                    'restaurant_id' => $g['restaurant']->id,
                    'branch_id' => $g['branchA']->id,
                    'restaurant_event_id' => $event->id,
                    'starts_at' => Carbon::now()->addHours(5),
                    'party_size' => 2,
                ]);
                $this->fail('Expected ValidationException.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('restaurant_event_id', $e->errors());
            }
        }
    }

    public function test_event_must_belong_to_same_restaurant(): void
    {
        $g1 = $this->seedRestaurantGraph('evt-r1');
        $g2 = $this->seedRestaurantGraph('evt-r2');

        $event = RestaurantEvent::create([
            'restaurant_id' => $g2['restaurant']->id,
            'branch_id' => $g2['branchA']->id,
            'title' => 'Other',
            'slug' => 'evt-other-restaurant',
            'starts_at' => Carbon::now()->addDays(2),
            'status' => RestaurantEvent::STATUS_PUBLISHED,
            'booking_mode' => RestaurantEvent::BOOKING_MODE_EVENT,
        ]);

        $this->expectException(ValidationException::class);
        app(ManualBookingCreationService::class)->create([
            'customer_phone' => '+15551110009',
            'customer_name' => 'Y',
            'restaurant_id' => $g1['restaurant']->id,
            'branch_id' => $g1['branchA']->id,
            'restaurant_event_id' => $event->id,
            'starts_at' => Carbon::now()->addHours(4),
            'party_size' => 2,
        ]);
    }

    public function test_branch_specific_event_requires_matching_booking_branch(): void
    {
        $g = $this->seedRestaurantGraph('evt-branch-specific');

        $event = RestaurantEvent::create([
            'restaurant_id' => $g['restaurant']->id,
            'branch_id' => $g['branchA']->id,
            'title' => 'Branch A only',
            'slug' => 'evt-branch-a-only',
            'starts_at' => Carbon::now()->addDays(2),
            'status' => RestaurantEvent::STATUS_PUBLISHED,
            'booking_mode' => RestaurantEvent::BOOKING_MODE_EVENT,
        ]);

        $this->expectException(ValidationException::class);
        app(ManualBookingCreationService::class)->create([
            'customer_phone' => '+15551110010',
            'customer_name' => 'Z',
            'restaurant_id' => $g['restaurant']->id,
            'branch_id' => $g['branchB']->id,
            'restaurant_event_id' => $event->id,
            'starts_at' => Carbon::now()->addHours(4),
            'party_size' => 2,
        ]);
    }

    public function test_restaurant_wide_event_can_be_used_by_any_branch_under_same_restaurant(): void
    {
        $g = $this->seedRestaurantGraph('evt-wide');

        $event = RestaurantEvent::create([
            'restaurant_id' => $g['restaurant']->id,
            'branch_id' => null,
            'title' => 'Restaurant-wide',
            'slug' => 'evt-wide-1',
            'starts_at' => Carbon::now()->addDays(2),
            'status' => RestaurantEvent::STATUS_PUBLISHED,
            'booking_mode' => RestaurantEvent::BOOKING_MODE_NORMAL,
        ]);

        $booking = app(ManualBookingCreationService::class)->create([
            'customer_phone' => '+15551110011',
            'customer_name' => 'W',
            'restaurant_id' => $g['restaurant']->id,
            'branch_id' => $g['branchB']->id,
            'restaurant_event_id' => $event->id,
            'starts_at' => Carbon::now()->addHours(4),
            'party_size' => 2,
        ]);

        $this->assertSame($event->id, $booking->restaurant_event_id);
    }

    public function test_event_capacity_blocks_overbooking_and_non_consuming_statuses_do_not_count(): void
    {
        $g = $this->seedRestaurantGraph('evt-capacity');
        $customer = $this->makeCustomer('cap@eventaat.test', '+15551110012');

        $event = RestaurantEvent::create([
            'restaurant_id' => $g['restaurant']->id,
            'branch_id' => $g['branchA']->id,
            'title' => 'Cap',
            'slug' => 'evt-cap',
            'starts_at' => Carbon::now()->addDays(2),
            'status' => RestaurantEvent::STATUS_PUBLISHED,
            'booking_mode' => RestaurantEvent::BOOKING_MODE_EVENT,
            'capacity' => 4,
        ]);

        // Consuming: pending (2) + seated (1) = 3
        Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $g['restaurant']->id,
            'branch_id' => $g['branchA']->id,
            'starts_at' => Carbon::now()->addHours(4),
            'party_size' => 2,
            'status' => BookingStatus::Pending,
            'restaurant_event_id' => $event->id,
        ]);

        Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $g['restaurant']->id,
            'branch_id' => $g['branchA']->id,
            'starts_at' => Carbon::now()->addHours(5),
            'party_size' => 1,
            'status' => BookingStatus::Seated,
            'restaurant_event_id' => $event->id,
        ]);

        // Non-consuming: cancelled (99) should not count.
        Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $g['restaurant']->id,
            'branch_id' => $g['branchA']->id,
            'starts_at' => Carbon::now()->addHours(6),
            'party_size' => 99,
            'status' => BookingStatus::Cancelled,
            'restaurant_event_id' => $event->id,
        ]);

        // New booking party_size=2 would exceed (3+2 > 4)
        $this->expectException(ValidationException::class);
        app(ManualBookingCreationService::class)->create([
            'customer_phone' => '+15551110013',
            'customer_name' => 'Over',
            'restaurant_id' => $g['restaurant']->id,
            'branch_id' => $g['branchA']->id,
            'restaurant_event_id' => $event->id,
            'starts_at' => Carbon::now()->addHours(7),
            'party_size' => 2,
        ]);
    }
}

