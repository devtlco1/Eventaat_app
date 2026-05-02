<?php

namespace App\Enums;

enum RestaurantSubscriptionStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case PastDue = 'past_due';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Trial => 'Trial',
            self::Active => 'Active',
            self::PastDue => 'Past due',
            self::Cancelled => 'Cancelled',
            self::Expired => 'Expired',
        };
    }

    /** Slot-taking statuses: at most one per restaurant (trial + active enforced). */
    public function occupiesRestaurantSlot(): bool
    {
        return match ($this) {
            self::Trial, self::Active => true,
            default => false,
        };
    }
}
