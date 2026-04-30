<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\BranchStatus;
use App\Enums\RestaurantStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\Bookings\BookingTransitionException;
use App\Services\Bookings\BookingTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BookingTransitionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeBooking(array $overrides = []): Booking
    {
        static $seq = 1;

        $customer = User::create([
            'name' => '',
            'phone' => '+1555999'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
            'email' => "transition_test_{$seq}@eventaat.test",
            'password' => Hash::make('x'),
        ]);

        $restaurant = Restaurant::create([
            'name' => 'R',
            'slug' => "r-transition-{$seq}",
            'status' => RestaurantStatus::Active,
        ]);

        $branch = Branch::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'B',
            'code' => "b{$seq}",
            'status' => BranchStatus::Active,
        ]);

        $seq++;

        return Booking::create(array_merge([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'branch_id' => $branch->id,
            'starts_at' => Carbon::now()->addHours(3),
            'party_size' => 2,
            'status' => BookingStatus::Pending,
        ], $overrides));
    }

    public function test_pending_can_accept_reject_or_cancel_and_timestamps_set(): void
    {
        $booking = $this->makeBooking([
            'status' => BookingStatus::Pending,
            'accepted_at' => null,
            'rejected_at' => null,
            'cancelled_at' => null,
        ]);

        $svc = app(BookingTransitionService::class);

        $svc->accept($booking, Carbon::parse('2026-01-01 10:00:00'));
        $booking->refresh();
        $this->assertSame('accepted', $booking->status->value);
        $this->assertNotNull($booking->accepted_at);

        $this->expectException(BookingTransitionException::class);
        $svc->reject($booking);
    }

    public function test_can_accept_returns_false_when_final_timestamp_is_set_even_if_status_is_pending(): void
    {
        $booking = $this->makeBooking([
            'status' => BookingStatus::Pending,
            'cancelled_at' => now(),
        ]);

        $svc = app(BookingTransitionService::class);

        $this->assertFalse($svc->canAccept($booking));
    }

    public function test_can_cancel_returns_false_when_final_timestamp_is_set_even_if_status_is_pending(): void
    {
        $booking = $this->makeBooking([
            'status' => BookingStatus::Pending,
            'rejected_at' => now(),
        ]);

        $svc = app(BookingTransitionService::class);

        $this->assertFalse($svc->canCancel($booking));
    }

    public function test_accepted_can_cancel_but_cannot_reject(): void
    {
        $booking = $this->makeBooking([
            'status' => BookingStatus::Accepted,
            'accepted_at' => now(),
        ]);

        $svc = app(BookingTransitionService::class);

        $svc->cancel($booking);
        $booking->refresh();
        $this->assertSame('cancelled', $booking->status->value);
        $this->assertNotNull($booking->cancelled_at);

        $this->expectException(BookingTransitionException::class);
        $svc->reject($booking);
    }

    public function test_accepted_can_be_marked_arrived_and_timestamp_is_set(): void
    {
        $svc = app(BookingTransitionService::class);

        $booking = $this->makeBooking([
            'status' => BookingStatus::Accepted,
            'accepted_at' => Carbon::parse('2026-01-01 10:00:00'),
        ]);

        $svc->arrive($booking, Carbon::parse('2026-01-01 11:00:00'));
        $booking->refresh();
        $this->assertSame('arrived', $booking->status->value);
        $this->assertSame('2026-01-01 11:00:00', $booking->arrived_at?->format('Y-m-d H:i:s'));
    }

    public function test_accepted_can_be_marked_no_show_and_timestamp_is_set(): void
    {
        $svc = app(BookingTransitionService::class);

        $booking = $this->makeBooking([
            'status' => BookingStatus::Accepted,
            'accepted_at' => Carbon::parse('2026-01-01 10:00:00'),
        ]);

        $svc->noShow($booking, Carbon::parse('2026-01-01 11:00:00'));
        $booking->refresh();
        $this->assertSame('no_show', $booking->status->value);
        $this->assertSame('2026-01-01 11:00:00', $booking->no_show_at?->format('Y-m-d H:i:s'));
    }

    public function test_arrived_can_be_marked_seated_or_no_show_or_cancelled_and_timestamps_set(): void
    {
        $svc = app(BookingTransitionService::class);

        $arrived = $this->makeBooking([
            'status' => BookingStatus::Arrived,
            'accepted_at' => Carbon::parse('2026-01-01 10:00:00'),
            'arrived_at' => Carbon::parse('2026-01-01 11:00:00'),
        ]);

        $svc->seat($arrived, Carbon::parse('2026-01-01 11:05:00'));
        $arrived->refresh();
        $this->assertSame('seated', $arrived->status->value);
        $this->assertSame('2026-01-01 11:05:00', $arrived->seated_at?->format('Y-m-d H:i:s'));

        $arrived2 = $this->makeBooking([
            'status' => BookingStatus::Arrived,
            'accepted_at' => Carbon::parse('2026-01-01 10:00:00'),
            'arrived_at' => Carbon::parse('2026-01-01 11:00:00'),
        ]);

        $svc->noShow($arrived2, Carbon::parse('2026-01-01 11:10:00'));
        $arrived2->refresh();
        $this->assertSame('no_show', $arrived2->status->value);
        $this->assertSame('2026-01-01 11:10:00', $arrived2->no_show_at?->format('Y-m-d H:i:s'));

        $arrived3 = $this->makeBooking([
            'status' => BookingStatus::Arrived,
            'accepted_at' => Carbon::parse('2026-01-01 10:00:00'),
            'arrived_at' => Carbon::parse('2026-01-01 11:00:00'),
        ]);

        $svc->cancel($arrived3, Carbon::parse('2026-01-01 11:15:00'));
        $arrived3->refresh();
        $this->assertSame('cancelled', $arrived3->status->value);
        $this->assertSame('2026-01-01 11:15:00', $arrived3->cancelled_at?->format('Y-m-d H:i:s'));
    }

    public function test_seated_can_be_marked_completed_and_timestamp_is_set(): void
    {
        $svc = app(BookingTransitionService::class);

        $booking = $this->makeBooking([
            'status' => BookingStatus::Seated,
            'accepted_at' => Carbon::parse('2026-01-01 10:00:00'),
            'arrived_at' => Carbon::parse('2026-01-01 11:00:00'),
            'seated_at' => Carbon::parse('2026-01-01 11:05:00'),
        ]);

        $svc->complete($booking, Carbon::parse('2026-01-01 12:00:00'));
        $booking->refresh();
        $this->assertSame('completed', $booking->status->value);
        $this->assertSame('2026-01-01 12:00:00', $booking->completed_at?->format('Y-m-d H:i:s'));
    }

    public function test_completed_no_show_rejected_and_cancelled_are_final_in_phase_6(): void
    {
        $svc = app(BookingTransitionService::class);

        $completed = $this->makeBooking([
            'status' => BookingStatus::Completed,
            'completed_at' => now(),
        ]);
        $this->expectException(BookingTransitionException::class);
        $svc->cancel($completed);
    }

    public function test_no_show_is_final_in_phase_6(): void
    {
        $svc = app(BookingTransitionService::class);

        $noShow = $this->makeBooking([
            'status' => BookingStatus::NoShow,
            'no_show_at' => now(),
        ]);

        $this->expectException(BookingTransitionException::class);
        $svc->seat($noShow);
    }

    public function test_rejected_and_cancelled_are_final_in_phase_6(): void
    {
        $svc = app(BookingTransitionService::class);

        $rejected = $this->makeBooking([
            'status' => BookingStatus::Rejected,
            'rejected_at' => now(),
        ]);

        $this->expectException(BookingTransitionException::class);
        $svc->accept($rejected);
    }
}

