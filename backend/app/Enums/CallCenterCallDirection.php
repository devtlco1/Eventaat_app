<?php

namespace App\Enums;

enum CallCenterCallDirection: string
{
    case Inbound = 'inbound';
    case Outbound = 'outbound';

    public function label(): string
    {
        return match ($this) {
            self::Inbound => 'Inbound',
            self::Outbound => 'Outbound',
        };
    }

    public function filamentColor(): string
    {
        return match ($this) {
            self::Inbound => 'info',
            self::Outbound => 'gray',
        };
    }
}
