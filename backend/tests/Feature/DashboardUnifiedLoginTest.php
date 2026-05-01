<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardUnifiedLoginTest extends TestCase
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

    public function test_guest_get_login_returns_200(): void
    {
        $this->get(route('dashboard.login'))
            ->assertOk()
            ->assertSee('Dashboard sign-in', false);
    }

    public function test_super_admin_post_login_redirects_to_platform(): void
    {
        $this->post(route('dashboard.login.authenticate'), [
            'email' => 'super_admin@eventaat.test',
            'password' => 'password',
        ])->assertRedirect('/platform');

        $this->assertAuthenticatedAs($this->user('super_admin@eventaat.test'));
    }

    public function test_operations_admin_post_login_redirects_to_platform(): void
    {
        $this->post(route('dashboard.login.authenticate'), [
            'email' => 'operations_admin@eventaat.test',
            'password' => 'password',
        ])->assertRedirect('/platform');

        $this->assertAuthenticatedAs($this->user('operations_admin@eventaat.test'));
    }

    public function test_restaurant_role_post_login_redirects_to_restaurant(): void
    {
        $this->post(route('dashboard.login.authenticate'), [
            'email' => 'restaurant_owner@eventaat.test',
            'password' => 'password',
        ])->assertRedirect('/restaurant');

        $this->assertAuthenticatedAs($this->user('restaurant_owner@eventaat.test'));
    }

    public function test_customer_post_login_is_denied_and_logged_out(): void
    {
        $response = $this->post(route('dashboard.login.authenticate'), [
            'email' => 'customer@eventaat.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard.login'));
        $response->assertSessionHas('dashboard_access_denied');
        $this->assertGuest();
    }

    public function test_authenticated_platform_get_login_redirects_to_platform(): void
    {
        $user = $this->user('super_admin@eventaat.test');

        $this->actingAs($user)
            ->get(route('dashboard.login'))
            ->assertRedirect('/platform');
    }

    public function test_authenticated_restaurant_get_login_redirects_to_restaurant(): void
    {
        $user = $this->user('restaurant_owner@eventaat.test');

        $this->actingAs($user)
            ->get(route('dashboard.login'))
            ->assertRedirect('/restaurant');
    }

    public function test_authenticated_customer_get_login_logs_out_and_redirects_with_message(): void
    {
        $customer = $this->user('customer@eventaat.test');

        $response = $this->actingAs($customer)->get(route('dashboard.login'));

        $response->assertRedirect(route('dashboard.login'));
        $response->assertSessionHas('dashboard_access_denied');
        $this->assertGuest();
    }

    public function test_platform_filament_login_still_reachable(): void
    {
        $status = $this->get('/platform/login')->getStatusCode();

        $this->assertContains($status, [200, 302]);
    }

    public function test_restaurant_filament_login_still_reachable(): void
    {
        $status = $this->get('/restaurant/login')->getStatusCode();

        $this->assertContains($status, [200, 302]);
    }

    public function test_invalid_credentials_show_validation_errors(): void
    {
        $this->post(route('dashboard.login.authenticate'), [
            'email' => 'super_admin@eventaat.test',
            'password' => 'wrong-password',
        ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_platform_role_wins_when_user_has_overlapping_roles(): void
    {
        $user = User::factory()->create([
            'email' => 'overlap-platform@eventaat.test',
        ]);
        $user->assignRole(['super_admin', 'restaurant_owner']);

        $this->post(route('dashboard.login.authenticate'), [
            'email' => 'overlap-platform@eventaat.test',
            'password' => 'password',
        ])->assertRedirect('/platform');

        $this->assertAuthenticatedAs($user);
    }
}
