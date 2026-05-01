<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentLogoutRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndTestUsersSeeder::class);
    }

    private function user(string $email): User
    {
        /** @var User $user */
        $user = User::where('email', $email)->firstOrFail();

        return $user;
    }

    public function test_super_admin_platform_logout_redirects_to_unified_login(): void
    {
        $this->actingAs($this->user('super_admin@eventaat.test'));

        $this->post(route('filament.platform.auth.logout'))
            ->assertRedirect(route('dashboard.login'));

        $this->assertGuest();
    }

    public function test_restaurant_owner_restaurant_logout_redirects_to_unified_login(): void
    {
        $this->actingAs($this->user('restaurant_owner@eventaat.test'));

        $this->post(route('filament.restaurant.auth.logout'))
            ->assertRedirect(route('dashboard.login'));

        $this->assertGuest();
    }

    public function test_login_redirects_authenticated_platform_user_to_platform(): void
    {
        $user = $this->user('super_admin@eventaat.test');

        $this->actingAs($user)
            ->get(route('dashboard.login'))
            ->assertRedirect('/platform');
    }

    public function test_login_redirects_authenticated_restaurant_user_to_restaurant(): void
    {
        $user = $this->user('restaurant_owner@eventaat.test');

        $this->actingAs($user)
            ->get(route('dashboard.login'))
            ->assertRedirect('/restaurant');
    }

    public function test_platform_filament_login_remains_reachable(): void
    {
        $status = $this->get('/platform/login')->getStatusCode();

        $this->assertContains($status, [200, 302]);
    }

    public function test_restaurant_filament_login_remains_reachable(): void
    {
        $status = $this->get('/restaurant/login')->getStatusCode();

        $this->assertContains($status, [200, 302]);
    }
}
