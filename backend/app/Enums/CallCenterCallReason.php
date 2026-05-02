<?php

namespace App\Enums;

enum CallCenterCallReason: string
{
    case BookingConfirmation = 'booking_confirmation';
    case BookingFollowUp = 'booking_follow_up';
    case Complaint = 'complaint';
    case RestaurantSupport = 'restaurant_support';
    case Billing = 'billing';
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::BookingConfirmation => 'Booking confirmation',
            self::BookingFollowUp => 'Booking follow-up',
            self::Complaint => 'Complaint',
            self::RestaurantSupport => 'Restaurant support',
            self::Billing => 'Billing',
            self::General => 'General',
        };
    }

    public function filamentColor(): string
    {
        return match ($this) {
            self::BookingConfirmation => 'success',
            self::BookingFollowUp => 'info',
            self::Complaint => 'danger',
            self::RestaurantSupport => 'warning',
            self::Billing => 'warning',
            self::General => 'gray',
        };
    }
}
