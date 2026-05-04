<?php

namespace Tests\Feature;

use App\Models\MessagingSettings;
use App\Models\User;
use Database\Seeders\RolesAndTestUsersSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7L — Messaging settings platform page access tests.
 *
 * Verifies that:
 *  - super_admin can view the page
 *  - operations_admin can view the page (read-only)
 *  - other roles cannot access the page
 */
class MessagingSettingsPlatformPageTest extends TestCase
{
    use RefreshDatabase;

    private const PAGE_PATH = '/platform/messaging-settings';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
    }

    // -------------------------------------------------------------------------
    // Page access
    // -------------------------------------------------------------------------

    public function test_super_admin_can_view_messaging_settings_page(): void
    {
        Filament::setCurrentPanel('platform');
        $this->actingAs(User::where('email', 'super_admin@eventaat.test')->firstOrFail());

        $this->get(self::PAGE_PATH)->assertOk();
    }

    public function test_operations_admin_can_view_messaging_settings_page(): void
    {
        Filament::setCurrentPanel('platform');
        $this->actingAs(User::where('email', 'operations_admin@eventaat.test')->firstOrFail());

        $this->get(self::PAGE_PATH)->assertOk();
    }

    public function test_restaurant_owner_cannot_access_messaging_settings_page(): void
    {
        Filament::setCurrentPanel('platform');
        $this->actingAs(User::where('email', 'restaurant_owner@eventaat.test')->firstOrFail());

        $this->get(self::PAGE_PATH)->assertForbidden();
    }

    public function test_unauthenticated_user_is_redirected_from_messaging_settings_page(): void
    {
        Filament::setCurrentPanel('platform');

        $this->get(self::PAGE_PATH)->assertRedirect();
    }

    // -------------------------------------------------------------------------
    // getOrCreate seeds a safe row if absent
    // -------------------------------------------------------------------------

    public function test_page_mount_seeds_safe_singleton_row_if_absent(): void
    {
        $this->assertDatabaseMissing('messaging_settings', ['id' => MessagingSettings::SINGLETON_ID]);

        Filament::setCurrentPanel('platform');
        $this->actingAs(User::where('email', 'super_admin@eventaat.test')->firstOrFail());
        $this->get(self::PAGE_PATH)->assertOk();

        $this->assertDatabaseHas('messaging_settings', [
            'id' => MessagingSettings::SINGLETON_ID,
            'external_messaging_enabled' => false,
            'whatsapp_otp_enabled' => false,
        ]);
    }

    // -------------------------------------------------------------------------
    // Consistent inclusion in PlatformOperationsConsistencyTest paths
    // -------------------------------------------------------------------------

    public function test_messaging_settings_page_path_is_accessible_by_both_ops_roles(): void
    {
        Filament::setCurrentPanel('platform');

        foreach (['super_admin@eventaat.test', 'operations_admin@eventaat.test'] as $email) {
            $this->flushSession();
            $this->actingAs(User::where('email', $email)->firstOrFail());

            $this->get(self::PAGE_PATH)->assertOk();
        }
    }
}
