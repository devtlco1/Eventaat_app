<?php

namespace App\Services\Bookings;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Support\Carbon;

class BookingTransitionService
{
    public function canAccept(Booking $booking): bool
    {
        return $booking->status?->value === BookingStatus::Pending->value;
    }

    public function canReject(Booking $booking): bool
    {
        return $booking->status?->value === BookingStatus::Pending->value;
    }

    public function canCancel(Booking $booking): bool
    {
        return in_array($booking->status?->value, [
            BookingStatus::Pending->value,
            BookingStatus::Accepted->value,
        ], true);
    }

    public function accept(Booking $booking, ?Carbon $now = null): Booking
    {
        if (! $this->canAccept($booking)) {
            throw new BookingTransitionException('Booking cannot be accepted from status: '.$booking->status?->value);
        }

        if ($booking->rejected_at || $booking->cancelled_at) {
            throw new BookingTransitionException('Booking has a final timestamp set and cannot be accepted.');
        }

        $now ??= now();

        $booking->status = BookingStatus::Accepted;
        $booking->accepted_at ??= $now;

        $booking->save();

        return $booking;
    }

    public function reject(Booking $booking, ?Carbon $now = null): Booking
    {
        if (! $this->canReject($booking)) {
            throw new BookingTransitionException('Booking cannot be rejected from status: '.$booking->status?->value);
        }

        if ($booking->accepted_at || $booking->cancelled_at) {
            throw new BookingTransitionException('Booking has a final timestamp set and cannot be rejected.');
        }

        $now ??= now();

        $booking->status = BookingStatus::Rejected;
        $booking->rejected_at ??= $now;

        $booking->save();

        return $booking;
    }

    public function cancel(Booking $booking, ?Carbon $now = null): Booking
    {
        if (! $this->canCancel($booking)) {
            throw new BookingTransitionException('Booking cannot be cancelled from status: '.$booking->status?->value);
        }

        if ($booking->rejected_at) {
            throw new BookingTransitionException('Rejected bookings cannot be cancelled in Phase 5A.');
        }

        $now ??= now();

        $booking->status = BookingStatus::Cancelled;
        $booking->cancelled_at ??= $now;

        $booking->save();

        return $booking;
    }
}

