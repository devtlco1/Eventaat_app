<?php

namespace App\Enums;

enum RestaurantInvoiceStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Paid = 'paid';
    case Void = 'void';
    case Overdue = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Issued => 'Issued',
            self::Paid => 'Paid',
            self::Void => 'Void',
            self::Overdue => 'Overdue',
        };
    }

    /** Filament badge color hint (semantic). */
    public function filamentColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Issued => 'info',
            self::Paid => 'success',
            self::Void => 'danger',
            self::Overdue => 'warning',
        };
    }
}
