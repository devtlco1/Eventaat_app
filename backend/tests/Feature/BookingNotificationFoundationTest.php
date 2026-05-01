<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\BranchStatus;
use App\Enums\RestaurantStatus;
use App\Models\Booking;
use App\Models\BookingNotification;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\Bookings\BookingTransitionException;
use App\Services\Bookings\BookingTransitionService;
use Database\Seeders\RolesAndTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BookingNotificationFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
    }

    private function assertNotification(string $event, Booking $booking): void
    {
        $this->assertDatabaseHas('booking_notifications', [
            'booking_id' => $booking->id,
            'event' => $event,
            'status' => 'pending',
            'channel' => 'internal',
        ]);
    }

    public function test_booking_transition_records_matching_notifications(): void
    {
        $customer = User::factory()->create();

        $restaurant = Restaurant::create([
            'name' => 'R',
            'slug' => 'r-notif-chain',
            'status' => RestaurantStatus::Active,
        ]);

        $branch = Branch::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'B',
            'code' => 'b-chain',
            'status' => BranchStatus::Active,
        ]);

        $booking = Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'branch_id' => $branch->id,
            'starts_at' => Carbon::now()->addDay(),
            'party_size' => 2,
            'status' => BookingStatus::Pending,
        ]);

        $svc = app(BookingTransitionService::class);

        $svc->accept($booking);
        $booking->refresh();
        $this->assertNotification(BookingNotification::EVENT_BOOKING_ACCEPTED, $booking);

        $svc->arrive($booking);
        $booking->refresh();
        $this->assertNotification(BookingNotification::EVENT_BOOKING_ARRIVED, $booking);

        $svc->seat($booking);
        $booking->refresh();
        $this->assertNotification(BookingNotification::EVENT_BOOKING_SEATED, $booking);

        $svc->complete($booking);
        $booking->refresh();
        $this->assertNotification(BookingNotification::EVENT_BOOKING_COMPLETED, $booking);
    }

    public function test_reject_pending_records_booking_rejected(): void
    {
        $booking = $this->makePendingBooking();

        app(BookingTransitionService::class)->reject($booking);

        $this->assertNotification(BookingNotification::EVENT_BOOKING_REJECTED, $booking);
    }

    public function test_cancel_pending_records_booking_cancelled(): void
    {
        $booking = $this->makePendingBooking();

        app(BookingTransitionService::class)->cancel($booking);

        $this->assertNotification(BookingNotification::EVENT_BOOKING_CANCELLED, $booking);
    }

    public function test_no_show_records_booking_no_show(): void
    {
        $booking = $this->makePendingBooking();

        $svc = app(BookingTransitionService::class);
        $svc->accept($booking);
        $booking->refresh();
        $svc->noShow($booking);

        $this->assertNotification(BookingNotification::EVENT_BOOKING_NO_SHOW, $booking);
    }

    public function test_invalid_transition_does_not_create_notification(): void
    {
        $booking = $this->makePendingBooking();
        app(BookingTransitionService::class)->accept($booking);
        $booking->refresh();

        $before = BookingNotification::count();

        try {
            app(BookingTransitionService::class)->reject($booking);
            $this->fail('Expected BookingTransitionException.');
        } catch (BookingTransitionException) {
            //
        }

        $this->assertSame($before, BookingNotification::count());
    }

    public function test_platform_super_admin_can_view_booking_notifications_index(): void
    {
        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        $this->actingAs($admin);

        $this->get('/platform/booking-notifications')->assertOk();
    }

    public function test_platform_booking_notifications_index_exposes_dispatch_actions_for_pending_internal_rows(): void
    {
        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        $this->actingAs($admin);

        $booking = $this->makePendingBooking();

        BookingNotification::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->customer_id,
            'channel' => 'internal',
            'event' => BookingNotification::EVENT_BOOKING_CREATED,
            'title' => 'T',
            'message' => 'M',
            'status' => 'pending',
        ]);

        $this->get('/platform/booking-notifications')
            ->assertOk()
            ->assertSee('Mark sent')
            ->assertSee('Mark skipped')
            ->assertSee('Mark failed')
            ->assertSee('Dry-run dispatch');
    }

    public function test_restaurant_owner_cannot_access_platform_booking_notifications(): void
    {
        $owner = User::where('email', 'restaurant_owner@eventaat.test')->firstOrFail();
        $this->actingAs($owner);

        $this->get('/platform/booking-notifications')->assertForbidden();
    }

    private function makePendingBooking(): Booking
    {
        static $seq = 1;
        $seq++;

        $customer = User::create([
            'name' => 'C',
            'phone' => '+1555888'.str_pad((string) $seq, 5, '0', STR_PAD_LEFT),
            'email' => "bnf{$seq}@eventaat.test",
            'password' => Hash::make('x'),
        ]);

        $restaurant = Restaurant::create([
            'name' => 'R',
            'slug' => "r-bnf-{$seq}",
            'status' => RestaurantStatus::Active,
        ]);

        $branch = Branch::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'B',
            'code' => "c{$seq}",
            'status' => BranchStatus::Active,
        ]);

        return Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'branch_id' => $branch->id,
            'starts_at' => Carbon::now()->addHours(4),
            'party_size' => 2,
            'status' => BookingStatus::Pending,
        ]);
    }
}
