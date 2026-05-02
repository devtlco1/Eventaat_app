<?php

namespace App\Support\Platform;

final class CoreRoles
{
    /** @var list<string> */
    public const NAMES = [
        'super_admin',
        'operations_admin',
        'restaurant_owner',
        'branch_manager',
        'restaurant_host',
        'customer',
    ];

    public static function isCore(string $name): bool
    {
        return in_array($name, self::NAMES, true);
    }
}
