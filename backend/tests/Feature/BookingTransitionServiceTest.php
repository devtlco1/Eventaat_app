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
        $customer = User::create([
            'name' => '',
            'phone' => '+15559990001',
            'email' => 'transition_test@eventaat.test',
            'password' => Hash::make('x'),
        ]);

        $restaurant = Restaurant::create([
            'name' => 'R',
            'slug' => 'r-transition',
            'status' => RestaurantStatus::Active,
        ]);

        $branch = Branch::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'B',
            'code' => 'b',
            'status' => BranchStatus::Active,
        ]);

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

    public function test_rejected_and_cancelled_are_final_in_phase_5a(): void
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

