<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTemplateResourceAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
    }

    public function test_platform_super_admin_can_view_notification_templates_index(): void
    {
        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        $this->actingAs($admin);

        $this->get('/platform/notification-templates')->assertOk();
    }

    public function test_platform_super_admin_can_view_notification_templates_create_page(): void
    {
        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        $this->actingAs($admin);

        $this->get('/platform/notification-templates/create')->assertOk();
    }

    public function test_restaurant_owner_cannot_access_platform_notification_templates(): void
    {
        $owner = User::where('email', 'restaurant_owner@eventaat.test')->firstOrFail();
        $this->actingAs($owner);

        $this->get('/platform/notification-templates')->assertForbidden();
    }

    public function test_restaurant_owner_cannot_access_platform_notification_templates_create_page(): void
    {
        $owner = User::where('email', 'restaurant_owner@eventaat.test')->firstOrFail();
        $this->actingAs($owner);

        $this->get('/platform/notification-templates/create')->assertForbidden();
    }
}

