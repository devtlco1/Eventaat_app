<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndTestUsersSeeder::class,
            RolePermissionDefaultsSeeder::class,
            SubscriptionPlansSeeder::class,
            NotificationTemplatesSeeder::class,
            RestaurantDemoSeeder::class,
            RestaurantEventsDemoSeeder::class,
            BookingDemoSeeder::class,
        ]);
    }
}
