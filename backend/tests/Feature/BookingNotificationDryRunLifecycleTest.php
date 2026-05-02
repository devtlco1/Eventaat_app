<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\BranchStatus;
use App\Enums\RestaurantStatus;
use App\Models\Booking;
use App\Models\BookingNotification;
use App\Models\Branch;
use App\Models\NotificationDispatchAttempt;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\Bookings\BookingTransitionService;
use App\Services\Notifications\BookingNotificationService;
use App\Services\Notifications\NotificationDispatchService;
use App\Services\Notifications\Providers\InternalDryRunNotificationProvider;
use Database\Seeders\RolesAndTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BookingNotificationDryRunLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
    }

    private function graph(): array
    {
        static $seq = 1;
        $seq++;

        $customer = User::create([
            'name' => 'Dry Customer',
            'phone' => '+15550777'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
            'email' => "dryrun_customer_{$seq}@eventaat.test",
            'password' => Hash::make('x'),
        ]);
        $customer->syncRoles(['customer']);

        $restaurant = Restaurant::create([
            'name' => 'Dry Restaurant',
            'slug' => "dry-restaurant-{$seq}",
            'status' => RestaurantStatus::Active,
        ]);

        $branch = Branch::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Dry Branch',
            'code' => "dry{$seq}",
            'status' => BranchStatus::Active,
        ]);

        return compact('customer', 'restaurant', 'branch');
    }

    private function pendingBooking(User $customer, Restaurant $restaurant, Branch $branch): Booking
    {
        return Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'branch_id' => $branch->id,
            'starts_at' => Carbon::parse('2026-06-15 19:45:00', config('app.timezone', 'UTC')),
            'party_size' => 4,
            'status' => BookingStatus::Pending,
        ]);
    }

    public function test_accept_reject_cancel_notifications_dispatch_with_internal_dry_run_provider(): void
    {
        $g = $this->graph();
        $booking = $this->pendingBooking($g['customer'], $g['restaurant'], $g['branch']);

        app(BookingTransitionService::class)->accept($booking);
        $acceptedRow = BookingNotification::query()
            ->where('booking_id', $booking->id)
            ->where('event', BookingNotification::EVENT_BOOKING_ACCEPTED)
            ->firstOrFail();

        $this->assertSame('pending', $acceptedRow->status);
        $this->assertTrue(app(NotificationDispatchService::class)->dispatchInternalDryRun($acceptedRow));
        $acceptedRow->refresh();
        $this->assertSame('sent', $acceptedRow->status);
        $this->assertDatabaseHas('notification_dispatch_attempts', [
            'booking_notification_id' => $acceptedRow->id,
            'provider' => InternalDryRunNotificationProvider::PROVIDER_NAME,
        ]);

        $g2 = $this->graph();
        $booking2 = $this->pendingBooking($g2['customer'], $g2['restaurant'], $g2['branch']);
        app(BookingTransitionService::class)->reject($booking2);
        $rejectRow = BookingNotification::query()
            ->where('booking_id', $booking2->id)
            ->where('event', BookingNotification::EVENT_BOOKING_REJECTED)
            ->firstOrFail();
        $this->assertTrue(app(NotificationDispatchService::class)->dispatchInternalDryRun($rejectRow));

        $g3 = $this->graph();
        $booking3 = $this->pendingBooking($g3['customer'], $g3['restaurant'], $g3['branch']);
        app(BookingTransitionService::class)->accept($booking3);
        app(BookingTransitionService::class)->cancel($booking3->fresh());
        $cancelRow = BookingNotification::query()
            ->where('booking_id', $booking3->id)
            ->where('event', BookingNotification::EVENT_BOOKING_CANCELLED)
            ->firstOrFail();
        $this->assertTrue(app(NotificationDispatchService::class)->dispatchInternalDryRun($cancelRow));

        $this->assertSame(3, NotificationDispatchAttempt::query()->count());
    }

    public function test_recorded_payload_includes_schedule_and_resolved_copy(): void
    {
        $g = $this->graph();
        $booking = $this->pendingBooking($g['customer'], $g['restaurant'], $g['branch']);

        app(BookingNotificationService::class)->record($booking, BookingNotification::EVENT_BOOKING_REQUESTED);

        $row = BookingNotification::query()
            ->where('booking_id', $booking->id)
            ->where('event', BookingNotification::EVENT_BOOKING_REQUESTED)
            ->firstOrFail();

        $payload = $row->payload;
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('booking_date', $payload);
        $this->assertArrayHasKey('booking_time', $payload);
        $this->assertArrayHasKey('resolved_title', $payload);
        $this->assertArrayHasKey('resolved_message', $payload);
        $this->assertSame('internal', $payload['channel'] ?? null);
    }
}
