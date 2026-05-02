<?php

namespace App\Enums;

enum CallCenterCallOutcome: string
{
    case Pending = 'pending';
    case Reached = 'reached';
    case NoAnswer = 'no_answer';
    case Busy = 'busy';
    case WrongNumber = 'wrong_number';
    case Resolved = 'resolved';
    case Escalated = 'escalated';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Reached => 'Reached',
            self::NoAnswer => 'No answer',
            self::Busy => 'Busy',
            self::WrongNumber => 'Wrong number',
            self::Resolved => 'Resolved',
            self::Escalated => 'Escalated',
        };
    }

    public function filamentColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Reached => 'success',
            self::NoAnswer => 'gray',
            self::Busy => 'warning',
            self::WrongNumber => 'danger',
            self::Resolved => 'success',
            self::Escalated => 'danger',
        };
    }
}
