<?php

namespace Tests\Feature;

use App\Models\MessagingSettings;
use App\Services\Notifications\MessagingSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7L — MessagingSettingsService unit-level tests.
 *
 * phpunit.xml pins OTP_DRIVER=log and NOTIFICATION_DRIVER=dry_run, so config
 * fallback assertions always resolve to the safe defaults.
 */
class MessagingSettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    private MessagingSettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MessagingSettingsService;
    }

    // -------------------------------------------------------------------------
    // No DB row → fall back to env/config defaults
    // -------------------------------------------------------------------------

    public function test_effective_otp_driver_falls_back_to_config_when_no_db_row(): void
    {
        $this->assertSame('log', $this->service->effectiveOtpDriver());
    }

    public function test_effective_notification_driver_falls_back_to_config_when_no_db_row(): void
    {
        $this->assertSame('dry_run', $this->service->effectiveNotificationDriver());
    }

    // -------------------------------------------------------------------------
    // Kill-switch: external_messaging_enabled = false
    // -------------------------------------------------------------------------

    public function test_kill_switch_off_forces_otp_to_log(): void
    {
        MessagingSettings::create([
            'id' => MessagingSettings::SINGLETON_ID,
            'external_messaging_enabled' => false,
            'otp_driver' => MessagingSettings::OTP_DRIVER_TWILIO_SMS,
            'notification_driver' => MessagingSettings::NOTIFICATION_DRIVER_TWILIO_SMS,
            'whatsapp_otp_enabled' => true,
        ]);

        $this->assertSame(MessagingSettings::OTP_DRIVER_LOG, $this->service->effectiveOtpDriver());
    }

    public function test_kill_switch_off_forces_notification_to_dry_run(): void
    {
        MessagingSettings::create([
            'id' => MessagingSettings::SINGLETON_ID,
            'external_messaging_enabled' => false,
            'otp_driver' => MessagingSettings::OTP_DRIVER_TWILIO_SMS,
            'notification_driver' => MessagingSettings::NOTIFICATION_DRIVER_TWILIO_SMS,
            'whatsapp_otp_enabled' => true,
        ]);

        $this->assertSame(MessagingSettings::NOTIFICATION_DRIVER_DRY_RUN, $this->service->effectiveNotificationDriver());
    }

    // -------------------------------------------------------------------------
    // Kill-switch on + explicit DB drivers
    // -------------------------------------------------------------------------

    public function test_kill_switch_on_uses_db_otp_driver(): void
    {
        MessagingSettings::create([
            'id' => MessagingSettings::SINGLETON_ID,
            'external_messaging_enabled' => true,
            'otp_driver' => MessagingSettings::OTP_DRIVER_TWILIO_SMS,
            'notification_driver' => null,
            'whatsapp_otp_enabled' => false,
        ]);

        $this->assertSame(MessagingSettings::OTP_DRIVER_TWILIO_SMS, $this->service->effectiveOtpDriver());
    }

    public function test_kill_switch_on_uses_db_notification_driver(): void
    {
        MessagingSettings::create([
            'id' => MessagingSettings::SINGLETON_ID,
            'external_messaging_enabled' => true,
            'otp_driver' => null,
            'notification_driver' => MessagingSettings::NOTIFICATION_DRIVER_TWILIO_SMS,
            'whatsapp_otp_enabled' => false,
        ]);

        $this->assertSame(MessagingSettings::NOTIFICATION_DRIVER_TWILIO_SMS, $this->service->effectiveNotificationDriver());
    }

    // -------------------------------------------------------------------------
    // WhatsApp guard
    // -------------------------------------------------------------------------

    public function test_whatsapp_driver_blocked_when_whatsapp_otp_enabled_false(): void
    {
        MessagingSettings::create([
            'id' => MessagingSettings::SINGLETON_ID,
            'external_messaging_enabled' => true,
            'otp_driver' => MessagingSettings::OTP_DRIVER_TWILIO_WHATSAPP,
            'notification_driver' => null,
            'whatsapp_otp_enabled' => false, // guard is off
        ]);

        // Must fall back to config default ('log') — never return twilio_whatsapp
        $this->assertSame('log', $this->service->effectiveOtpDriver());
        $this->assertNotSame(MessagingSettings::OTP_DRIVER_TWILIO_WHATSAPP, $this->service->effectiveOtpDriver());
    }

    public function test_whatsapp_driver_allowed_when_whatsapp_otp_enabled_true(): void
    {
        MessagingSettings::create([
            'id' => MessagingSettings::SINGLETON_ID,
            'external_messaging_enabled' => true,
            'otp_driver' => MessagingSettings::OTP_DRIVER_TWILIO_WHATSAPP,
            'notification_driver' => null,
            'whatsapp_otp_enabled' => true,
        ]);

        $this->assertSame(MessagingSettings::OTP_DRIVER_TWILIO_WHATSAPP, $this->service->effectiveOtpDriver());
    }

    // -------------------------------------------------------------------------
    // DB null driver → config fallback
    // -------------------------------------------------------------------------

    public function test_null_otp_driver_in_db_falls_back_to_config(): void
    {
        MessagingSettings::create([
            'id' => MessagingSettings::SINGLETON_ID,
            'external_messaging_enabled' => true,
            'otp_driver' => null, // explicit null → env/config
            'notification_driver' => null,
            'whatsapp_otp_enabled' => false,
        ]);

        // phpunit.xml pins OTP_DRIVER=log
        $this->assertSame('log', $this->service->effectiveOtpDriver());
    }

    public function test_null_notification_driver_in_db_falls_back_to_config(): void
    {
        MessagingSettings::create([
            'id' => MessagingSettings::SINGLETON_ID,
            'external_messaging_enabled' => true,
            'otp_driver' => null,
            'notification_driver' => null, // explicit null → env/config
            'whatsapp_otp_enabled' => false,
        ]);

        // phpunit.xml pins NOTIFICATION_DRIVER=dry_run
        $this->assertSame('dry_run', $this->service->effectiveNotificationDriver());
    }

    // -------------------------------------------------------------------------
    // Twilio credential presence (config values are empty in test env)
    // -------------------------------------------------------------------------

    public function test_has_twilio_sms_config_is_false_when_credentials_empty(): void
    {
        // phpunit.xml sets all TWILIO_* to '' so this must be false in test env
        $this->assertFalse($this->service->hasTwilioSmsConfig());
    }

    public function test_has_twilio_whatsapp_config_is_false_when_credentials_empty(): void
    {
        $this->assertFalse($this->service->hasTwilioWhatsAppConfig());
    }

    // -------------------------------------------------------------------------
    // MessagingSettings::getOrCreate() creates safe defaults
    // -------------------------------------------------------------------------

    public function test_get_or_create_returns_safe_defaults(): void
    {
        $settings = MessagingSettings::getOrCreate();

        $this->assertSame(MessagingSettings::SINGLETON_ID, $settings->id);
        $this->assertFalse($settings->external_messaging_enabled);
        $this->assertNull($settings->otp_driver);
        $this->assertNull($settings->notification_driver);
        $this->assertFalse($settings->whatsapp_otp_enabled);
    }

    public function test_get_or_create_is_idempotent(): void
    {
        MessagingSettings::getOrCreate();
        MessagingSettings::getOrCreate(); // should not throw or duplicate

        $this->assertSame(1, MessagingSettings::count());
    }
}
