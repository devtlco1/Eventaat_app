<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndTestUsersSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAuthorizationHelpersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
    }

    public function test_user_authorization_helpers_align_with_filament_panel_rules(): void
    {
        $platform = Filament::getPanel('platform');
        $restaurant = Filament::getPanel('restaurant');

        $super = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        $this->assertTrue($super->isPlatformOperator());
        $this->assertFalse($super->isRestaurantStaff());
        $this->assertTrue($super->canAccessPanel($platform));
        $this->assertFalse($super->canAccessPanel($restaurant));

        $ops = User::where('email', 'operations_admin@eventaat.test')->firstOrFail();
        $this->assertTrue($ops->isPlatformOperator());

        $owner = User::where('email', 'restaurant_owner@eventaat.test')->firstOrFail();
        $this->assertFalse($owner->isPlatformOperator());
        $this->assertTrue($owner->isRestaurantStaff());
        $this->assertTrue($owner->canManageRestaurantStructure());
        $this->assertTrue($owner->isRestaurantOwner());
        $this->assertTrue($owner->canAccessPanel($restaurant));
        $this->assertFalse($owner->canAccessPanel($platform));

        $manager = User::where('email', 'branch_manager@eventaat.test')->firstOrFail();
        $this->assertTrue($manager->isRestaurantStaff());
        $this->assertTrue($manager->canManageRestaurantStructure());
        $this->assertFalse($manager->isRestaurantOwner());

        $host = User::where('email', 'restaurant_host@eventaat.test')->firstOrFail();
        $this->assertTrue($host->isRestaurantStaff());
        $this->assertFalse($host->canManageRestaurantStructure());
        $this->assertFalse($host->isRestaurantOwner());

        $customer = User::where('email', 'customer@eventaat.test')->firstOrFail();
        $this->assertFalse($customer->canAccessPanel($platform));
        $this->assertFalse($customer->canAccessPanel($restaurant));
    }
}
