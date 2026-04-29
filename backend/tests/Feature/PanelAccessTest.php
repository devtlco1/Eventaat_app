<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelAccessTest extends TestCase
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

    private function assertAllowedResponse(int $status): void
    {
        $this->assertTrue(
            in_array($status, [200, 302], true),
            "Expected allowed response status [200 or 302], got [{$status}].",
        );
    }

    public function test_platform_roles_can_access_platform_panel(): void
    {
        $this->actingAs($this->user('super_admin@eventaat.test'));
        $this->assertAllowedResponse($this->get('/platform')->getStatusCode());

        $this->actingAs($this->user('operations_admin@eventaat.test'));
        $this->assertAllowedResponse($this->get('/platform')->getStatusCode());
    }

    public function test_restaurant_roles_cannot_access_platform_panel(): void
    {
        foreach ([
            'restaurant_owner@eventaat.test',
            'branch_manager@eventaat.test',
            'restaurant_host@eventaat.test',
        ] as $email) {
            $this->actingAs($this->user($email));
            $this->get('/platform')->assertForbidden();
        }
    }

    public function test_customer_cannot_access_platform_panel(): void
    {
        $this->actingAs($this->user('customer@eventaat.test'));
        $this->get('/platform')->assertForbidden();
    }

    public function test_restaurant_roles_can_access_restaurant_panel(): void
    {
        foreach ([
            'restaurant_owner@eventaat.test',
            'branch_manager@eventaat.test',
            'restaurant_host@eventaat.test',
        ] as $email) {
            $this->actingAs($this->user($email));
            $this->assertAllowedResponse($this->get('/restaurant')->getStatusCode());
        }
    }

    public function test_platform_roles_cannot_access_restaurant_panel(): void
    {
        foreach ([
            'super_admin@eventaat.test',
            'operations_admin@eventaat.test',
        ] as $email) {
            $this->actingAs($this->user($email));
            $this->get('/restaurant')->assertForbidden();
        }
    }

    public function test_customer_cannot_access_restaurant_panel(): void
    {
        $this->actingAs($this->user('customer@eventaat.test'));
        $this->get('/restaurant')->assertForbidden();
    }
}

