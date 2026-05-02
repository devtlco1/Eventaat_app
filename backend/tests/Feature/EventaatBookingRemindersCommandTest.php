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
use App\Services\Bookings\BookingTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EventaatBookingRemindersCommandTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @return array{customer: User, restaurant: Restaurant, branch: Branch} */
    private function graph(): array
    {
        self::$seq++;
        $s = self::$seq;

        $customer = User::create([
            'name' => 'Reminder Customer',
            'phone' => '+15550888'.str_pad((string) $s, 4, '0', STR_PAD_LEFT),
            'email' => "reminder_customer_{$s}@eventaat.test",
            'password' => Hash::make('x'),
        ]);

        $restaurant = Restaurant::create([
            'name' => 'Reminder Restaurant',
            'slug' => "reminder-restaurant-{$s}",
            'status' => RestaurantStatus::Active,
        ]);

        $branch = Branch::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Reminder Branch',
            'code' => "rmb{$s}",
            'status' => BranchStatus::Active,
        ]);

        return compact('customer', 'restaurant', 'branch');
    }

    private function makeAcceptedBooking(User $customer, Restaurant $restaurant, Branch $branch, Carbon $startsAt): Booking
    {
        $booking = Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'branch_id' => $branch->id,
            'starts_at' => $startsAt,
            'party_size' => 2,
            'status' => BookingStatus::Pending,
        ]);

        app(BookingTransitionService::class)->accept($booking);

        return $booking->fresh();
    }

    public function test_accepted_booking_inside_reminder_window_gets_one_arrival_reminder(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-15 10:00:00', config('app.timezone', 'UTC')));

        $g = $this->graph();
        $startsAt = Carbon::parse('2026-06-15 11:30:00', config('app.timezone', 'UTC'));
        $booking = $this->makeAcceptedBooking($g['customer'], $g['restaurant'], $g['branch'], $startsAt);

        Artisan::call('eventaat:booking-reminders');

        $this->assertSame(1, BookingNotification::query()
            ->where('booking_id', $booking->id)
            ->where('event', BookingNotification::EVENT_BOOKING_ARRIVAL_REMINDER)
            ->count());

        $row = BookingNotification::query()
            ->where('booking_id', $booking->id)
            ->where('event', BookingNotification::EVENT_BOOKING_ARRIVAL_REMINDER)
            ->firstOrFail();

        $payload = $row->payload;
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('booking_date', $payload);
        $this->assertArrayHasKey('booking_time', $payload);
        $this->assertSame('2026-06-15', $payload['booking_date']);
        $this->assertSame('11:30', $payload['booking_time']);
    }

    public function test_running_command_twice_does_not_duplicate_reminders(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-15 10:00:00', config('app.timezone', 'UTC')));

        $g = $this->graph();
        $booking = $this->makeAcceptedBooking(
            $g['customer'],
            $g['restaurant'],
            $g['branch'],
            Carbon::parse('2026-06-15 11:00:00', config('app.timezone', 'UTC')),
        );

        Artisan::call('eventaat:booking-reminders');
        Artisan::call('eventaat:booking-reminders');

        $this->assertSame(1, BookingNotification::query()
            ->where('booking_id', $booking->id)
            ->where('event', BookingNotification::EVENT_BOOKING_ARRIVAL_REMINDER)
            ->count());
    }

    public function test_booking_outside_reminder_window_gets_no_reminder(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-15 10:00:00', config('app.timezone', 'UTC')));

        $g = $this->graph();
        $booking = $this->makeAcceptedBooking(
            $g['customer'],
            $g['restaurant'],
            $g['branch'],
            Carbon::parse('2026-06-15 13:01:00', config('app.timezone', 'UTC')),
        );

        Artisan::call('eventaat:booking-reminders');

        $this->assertSame(0, BookingNotification::query()
            ->where('booking_id', $booking->id)
            ->where('event', BookingNotification::EVENT_BOOKING_ARRIVAL_REMINDER)
            ->count());
    }

    public function test_pending_booking_inside_window_gets_no_reminder(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-15 10:00:00', config('app.timezone', 'UTC')));

        $g = $this->graph();
        Booking::create([
            'customer_id' => $g['customer']->id,
            'restaurant_id' => $g['restaurant']->id,
            'branch_id' => $g['branch']->id,
            'starts_at' => Carbon::parse('2026-06-15 11:00:00', config('app.timezone', 'UTC')),
            'party_size' => 2,
            'status' => BookingStatus::Pending,
        ]);

        Artisan::call('eventaat:booking-reminders');

        $this->assertSame(0, BookingNotification::query()
            ->where('event', BookingNotification::EVENT_BOOKING_ARRIVAL_REMINDER)
            ->count());
    }

    public function test_non_accepted_statuses_inside_window_get_no_reminders(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-15 10:00:00', config('app.timezone', 'UTC')));
        $starts = Carbon::parse('2026-06-15 11:00:00', config('app.timezone', 'UTC'));

        foreach ([
            BookingStatus::Rejected,
            BookingStatus::Cancelled,
            BookingStatus::Completed,
            BookingStatus::NoShow,
            BookingStatus::Arrived,
        ] as $status) {
            $g = $this->graph();
            Booking::create([
                'customer_id' => $g['customer']->id,
                'restaurant_id' => $g['restaurant']->id,
                'branch_id' => $g['branch']->id,
                'starts_at' => $starts,
                'party_size' => 2,
                'status' => $status,
            ]);
        }

        Artisan::call('eventaat:booking-reminders');

        $this->assertSame(0, BookingNotification::query()
            ->where('event', BookingNotification::EVENT_BOOKING_ARRIVAL_REMINDER)
            ->count());
    }

    public function test_accepted_booking_with_past_starts_at_gets_no_reminder(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00', config('app.timezone', 'UTC')));

        $g = $this->graph();
        Booking::create([
            'customer_id' => $g['customer']->id,
            'restaurant_id' => $g['restaurant']->id,
            'branch_id' => $g['branch']->id,
            'starts_at' => Carbon::parse('2026-06-15 11:00:00', config('app.timezone', 'UTC')),
            'party_size' => 2,
            'status' => BookingStatus::Accepted,
            'accepted_at' => Carbon::parse('2026-06-15 09:00:00', config('app.timezone', 'UTC')),
        ]);

        Artisan::call('eventaat:booking-reminders');

        $this->assertSame(0, BookingNotification::query()
            ->where('event', BookingNotification::EVENT_BOOKING_ARRIVAL_REMINDER)
            ->count());
    }
}
