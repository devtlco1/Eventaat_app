<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Arrived = 'arrived';
    case Seated = 'seated';
    case Completed = 'completed';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::NoShow => 'لم يحضر',
            self::Pending => 'Pending',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
            self::Arrived => 'Arrived',
            self::Seated => 'Seated',
            self::Completed => 'Completed',
        };
    }
}

