<?php

namespace App\Services\Bookings;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Support\Carbon;

class BookingTransitionService
{
    private function hasFinalTimestamp(Booking $booking): bool
    {
        return (bool) ($booking->rejected_at || $booking->cancelled_at || $booking->completed_at || $booking->no_show_at);
    }

    public function isFinal(Booking $booking): bool
    {
        return in_array($booking->status?->value, [
            BookingStatus::Rejected->value,
            BookingStatus::Cancelled->value,
            BookingStatus::Completed->value,
            BookingStatus::NoShow->value,
        ], true);
    }

    public function canAccept(Booking $booking): bool
    {
        if ($this->isFinal($booking)) {
            return false;
        }

        if ($this->hasFinalTimestamp($booking)) {
            return false;
        }

        return $booking->status?->value === BookingStatus::Pending->value;
    }

    public function canReject(Booking $booking): bool
    {
        if ($this->isFinal($booking)) {
            return false;
        }

        if ($booking->accepted_at || $this->hasFinalTimestamp($booking)) {
            return false;
        }

        return $booking->status?->value === BookingStatus::Pending->value;
    }

    public function canCancel(Booking $booking): bool
    {
        if ($this->isFinal($booking)) {
            return false;
        }

        if ($booking->rejected_at || $booking->completed_at || $booking->no_show_at) {
            return false;
        }

        return in_array($booking->status?->value, [
            BookingStatus::Pending->value,
            BookingStatus::Accepted->value,
            BookingStatus::Arrived->value,
        ], true);
    }

    public function accept(Booking $booking, ?Carbon $now = null): Booking
    {
        if (! $this->canAccept($booking)) {
            throw new BookingTransitionException('Booking cannot be accepted from status: '.$booking->status?->value);
        }

        if ($booking->rejected_at || $booking->cancelled_at || $booking->completed_at || $booking->no_show_at) {
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

        if ($booking->accepted_at || $booking->cancelled_at || $booking->completed_at || $booking->no_show_at) {
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

        if ($booking->rejected_at || $booking->completed_at || $booking->no_show_at) {
            throw new BookingTransitionException('Booking has a final timestamp set and cannot be cancelled.');
        }

        $now ??= now();

        $booking->status = BookingStatus::Cancelled;
        $booking->cancelled_at ??= $now;

        $booking->save();

        return $booking;
    }

    public function canArrive(Booking $booking): bool
    {
        if ($this->isFinal($booking)) {
            return false;
        }

        if ($this->hasFinalTimestamp($booking)) {
            return false;
        }

        return $booking->status?->value === BookingStatus::Accepted->value;
    }

    public function arrive(Booking $booking, ?Carbon $now = null): Booking
    {
        if (! $this->canArrive($booking)) {
            throw new BookingTransitionException('Booking cannot be marked arrived from status: '.$booking->status?->value);
        }

        if ($booking->cancelled_at || $booking->rejected_at || $booking->completed_at || $booking->no_show_at) {
            throw new BookingTransitionException('Booking has a final timestamp set and cannot be marked arrived.');
        }

        $now ??= now();

        $booking->status = BookingStatus::Arrived;
        $booking->arrived_at ??= $now;

        $booking->save();

        return $booking;
    }

    public function canSeat(Booking $booking): bool
    {
        if ($this->isFinal($booking)) {
            return false;
        }

        if ($this->hasFinalTimestamp($booking)) {
            return false;
        }

        return $booking->status?->value === BookingStatus::Arrived->value;
    }

    public function seat(Booking $booking, ?Carbon $now = null): Booking
    {
        if (! $this->canSeat($booking)) {
            throw new BookingTransitionException('Booking cannot be marked seated from status: '.$booking->status?->value);
        }

        if ($booking->cancelled_at || $booking->rejected_at || $booking->completed_at || $booking->no_show_at) {
            throw new BookingTransitionException('Booking has a final timestamp set and cannot be marked seated.');
        }

        $now ??= now();

        $booking->status = BookingStatus::Seated;
        $booking->seated_at ??= $now;

        $booking->save();

        return $booking;
    }

    public function canComplete(Booking $booking): bool
    {
        if ($this->isFinal($booking)) {
            return false;
        }

        if ($booking->cancelled_at || $booking->rejected_at || $booking->no_show_at || $booking->completed_at) {
            return false;
        }

        return $booking->status?->value === BookingStatus::Seated->value;
    }

    public function complete(Booking $booking, ?Carbon $now = null): Booking
    {
        if (! $this->canComplete($booking)) {
            throw new BookingTransitionException('Booking cannot be marked completed from status: '.$booking->status?->value);
        }

        if ($booking->cancelled_at || $booking->rejected_at || $booking->no_show_at) {
            throw new BookingTransitionException('Booking has a final timestamp set and cannot be marked completed.');
        }

        $now ??= now();

        $booking->status = BookingStatus::Completed;
        $booking->completed_at ??= $now;

        $booking->save();

        return $booking;
    }

    public function canNoShow(Booking $booking): bool
    {
        if ($this->isFinal($booking)) {
            return false;
        }

        if ($booking->cancelled_at || $booking->rejected_at || $booking->completed_at) {
            return false;
        }

        return in_array($booking->status?->value, [
            BookingStatus::Accepted->value,
            BookingStatus::Arrived->value,
        ], true);
    }

    public function noShow(Booking $booking, ?Carbon $now = null): Booking
    {
        if (! $this->canNoShow($booking)) {
            throw new BookingTransitionException('Booking cannot be marked no-show from status: '.$booking->status?->value);
        }

        if ($booking->cancelled_at || $booking->rejected_at || $booking->completed_at) {
            throw new BookingTransitionException('Booking has a final timestamp set and cannot be marked no-show.');
        }

        $now ??= now();

        $booking->status = BookingStatus::NoShow;
        $booking->no_show_at ??= $now;

        $booking->save();

        return $booking;
    }
}

