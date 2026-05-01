<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\BranchStatus;
use App\Enums\RestaurantStatus;
use App\Models\Booking;
use App\Models\BookingNotification;
use App\Models\Branch;
use App\Models\NotificationTemplate;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\Bookings\BookingTransitionService;
use App\Services\Bookings\ManualBookingCreationService;
use Database\Seeders\RolesAndTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BookingNotificationTemplatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
    }

    private function makePendingBooking(): Booking
    {
        $customer = User::create([
            'name' => 'Customer Name',
            'phone' => '+15550100001',
            'email' => 'customer_templates@eventaat.test',
            'password' => Hash::make('x'),
        ]);
        $customer->syncRoles(['customer']);

        $restaurant = Restaurant::create([
            'name' => 'R',
            'slug' => 'r-templates',
            'status' => RestaurantStatus::Active,
        ]);

        $branch = Branch::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'B',
            'code' => 'b-templates',
            'status' => BranchStatus::Active,
        ]);

        return Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'branch_id' => $branch->id,
            'starts_at' => Carbon::parse('2026-05-02 18:30:00'),
            'party_size' => 2,
            'status' => BookingStatus::Pending,
        ]);
    }

    public function test_booking_created_notification_uses_active_template_when_available(): void
    {
        NotificationTemplate::create([
            'event' => BookingNotification::EVENT_BOOKING_CREATED,
            'channel' => 'internal',
            'locale' => 'en',
            'title_template' => 'Requested: #{{booking_id}}',
            'body_template' => 'New booking for {{party_size}} at {{restaurant_name}} ({{branch_name}}).',
            'is_active' => true,
        ]);

        $restaurant = Restaurant::create([
            'name' => 'R2',
            'slug' => 'r2-templates',
            'status' => RestaurantStatus::Active,
        ]);

        $branch = Branch::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'B2',
            'code' => 'b2-templates',
            'status' => BranchStatus::Active,
        ]);

        $booking = app(ManualBookingCreationService::class)->create([
            'customer_phone' => ' +15550100002 ',
            'customer_name' => 'Alice',
            'restaurant_id' => $restaurant->id,
            'branch_id' => $branch->id,
            'starts_at' => Carbon::now()->addHours(3),
            'party_size' => 3,
        ]);

        $row = BookingNotification::query()
            ->where('booking_id', $booking->id)
            ->where('event', BookingNotification::EVENT_BOOKING_CREATED)
            ->firstOrFail();

        $this->assertSame("Requested: #{$booking->id}", $row->title);
        $this->assertSame('New booking for 3 at R2 (B2).', $row->message);
    }

    public function test_booking_accepted_notification_uses_active_template_when_available(): void
    {
        NotificationTemplate::create([
            'event' => BookingNotification::EVENT_BOOKING_ACCEPTED,
            'channel' => 'internal',
            'locale' => 'en',
            'title_template' => 'Accepted: #{{booking_id}}',
            'body_template' => 'Hi {{customer_name}}, your booking at {{restaurant_name}} is accepted.',
            'is_active' => true,
        ]);

        $booking = $this->makePendingBooking();

        app(BookingTransitionService::class)->accept($booking);
        $booking->refresh();

        $row = BookingNotification::query()
            ->where('booking_id', $booking->id)
            ->where('event', BookingNotification::EVENT_BOOKING_ACCEPTED)
            ->firstOrFail();

        $this->assertSame("Accepted: #{$booking->id}", $row->title);
        $this->assertSame('Hi Customer Name, your booking at R is accepted.', $row->message);
    }

    public function test_booking_accepted_notification_falls_back_when_template_missing(): void
    {
        $booking = $this->makePendingBooking();

        app(BookingTransitionService::class)->accept($booking);
        $booking->refresh();

        $row = BookingNotification::query()
            ->where('booking_id', $booking->id)
            ->where('event', BookingNotification::EVENT_BOOKING_ACCEPTED)
            ->firstOrFail();

        $this->assertSame('Booking accepted', $row->title);
        $this->assertStringContainsString("Booking #{$booking->id}", $row->message);
    }

    public function test_inactive_template_is_ignored(): void
    {
        NotificationTemplate::create([
            'event' => BookingNotification::EVENT_BOOKING_ACCEPTED,
            'channel' => 'internal',
            'locale' => 'en',
            'title_template' => 'SHOULD NOT USE',
            'body_template' => 'SHOULD NOT USE',
            'is_active' => false,
        ]);

        $booking = $this->makePendingBooking();

        app(BookingTransitionService::class)->accept($booking);
        $booking->refresh();

        $row = BookingNotification::query()
            ->where('booking_id', $booking->id)
            ->where('event', BookingNotification::EVENT_BOOKING_ACCEPTED)
            ->firstOrFail();

        $this->assertSame('Booking accepted', $row->title);
        $this->assertStringContainsString("Booking #{$booking->id}", $row->message);
    }
}

