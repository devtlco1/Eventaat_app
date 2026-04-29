<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RolesAndTestUsersSeeder extends Seeder
{
    /**
     * Phase 1 testing users (local/dev only).
     *
     * Idempotent: safe to run multiple times.
     */
    public function run(): void
    {
        $roles = [
            // Platform
            'super_admin',
            'operations_admin',

            // Restaurant
            'restaurant_owner',
            'branch_manager',
            'restaurant_host',

            // Customer
            'customer',
        ];

        foreach ($roles as $role) {
            Role::findOrCreate($role, 'web');
        }

        $defaultPassword = 'password';

        $users = [
            ['name' => 'Super Admin', 'email' => 'super_admin@eventaat.test', 'role' => 'super_admin'],
            ['name' => 'Operations Admin', 'email' => 'operations_admin@eventaat.test', 'role' => 'operations_admin'],
            ['name' => 'Restaurant Owner', 'email' => 'restaurant_owner@eventaat.test', 'role' => 'restaurant_owner'],
            ['name' => 'Branch Manager', 'email' => 'branch_manager@eventaat.test', 'role' => 'branch_manager'],
            ['name' => 'Restaurant Host', 'email' => 'restaurant_host@eventaat.test', 'role' => 'restaurant_host'],
            ['name' => 'Customer', 'email' => 'customer@eventaat.test', 'role' => 'customer'],
        ];

        foreach ($users as $spec) {
            $user = User::firstOrCreate(
                ['email' => $spec['email']],
                ['name' => $spec['name'], 'password' => Hash::make($defaultPassword)],
            );

            // Keep local/dev credentials predictable.
            $user->forceFill([
                'name' => $spec['name'],
                'password' => Hash::make($defaultPassword),
            ])->save();

            $user->syncRoles([$spec['role']]);
        }

        // Phase 0 admin user: assign super_admin so it keeps working.
        $phase0Admin = User::where('email', 'admin@eventaat.test')->first();
        if ($phase0Admin) {
            $phase0Admin->syncRoles(['super_admin']);
        }
    }
}

