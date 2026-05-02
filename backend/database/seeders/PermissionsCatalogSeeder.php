<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

/**
 * Idempotent catalog of Spatie permission names for dashboard readiness / metadata.
 * Filament panel access remains role-based unless resources explicitly check permissions.
 */
class PermissionsCatalogSeeder extends Seeder
{
    /** @return list<string> */
    public static function names(): array
    {
        return [
            'users.view',
            'users.manage',
            'roles.view',
            'roles.manage',
            'permissions.view',
            'restaurants.manage',
            'bookings.manage',
            'menus.manage',
            'offers.manage',
            'events.manage',
            'stories.manage',
            'reviews.manage',
            'support_tickets.manage',
            'subscriptions.manage',
            'invoices.manage',
            'call_logs.manage',
            'notification_templates.manage',
        ];
    }

    public function run(): void
    {
        foreach (static::names() as $name) {
            Permission::findOrCreate($name, 'web');
        }
    }
}
