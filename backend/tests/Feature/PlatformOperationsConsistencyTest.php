<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndTestUsersSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformOperationsConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
    }

    /** @return list<string> */
    private function operationsIndexPaths(): array
    {
        return [
            '/platform/users',
            '/platform/bookings',
            '/platform/booking-notifications',
            '/platform/otp-delivery-attempts',
            '/platform/notification-templates',
            '/platform/restaurant-events',
            '/platform/restaurant-offers',
            '/platform/restaurant-menus',
            '/platform/restaurant-reviews',
            '/platform/restaurant-stories',
            '/platform/support-tickets',
            '/platform/subscription-plans',
            '/platform/restaurant-subscriptions',
            '/platform/restaurant-invoices',
            '/platform/call-center-calls',
            '/platform/messaging-settings',
        ];
    }

    public function test_platform_operations_roles_can_open_operations_indexes(): void
    {
        Filament::setCurrentPanel('platform');

        foreach (['super_admin@eventaat.test', 'operations_admin@eventaat.test'] as $email) {
            $this->flushSession();
            $this->actingAs(User::where('email', $email)->firstOrFail());

            foreach ($this->operationsIndexPaths() as $path) {
                $this->get($path)->assertOk();
            }
        }
    }

    public function test_restaurant_owner_cannot_open_platform_operations_indexes(): void
    {
        Filament::setCurrentPanel('platform');
        $this->flushSession();
        $this->actingAs(User::where('email', 'restaurant_owner@eventaat.test')->firstOrFail());

        foreach ($this->operationsIndexPaths() as $path) {
            $this->get($path)->assertForbidden();
        }
    }
}
