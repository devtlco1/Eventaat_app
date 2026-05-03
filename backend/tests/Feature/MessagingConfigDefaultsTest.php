<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Phase 7K — Messaging production toggle audit.
 *
 * Verifies that the config file ships safe defaults so an unset (or freshly
 * copied) .env never accidentally enables live Twilio SMS/WhatsApp.
 *
 * These tests deliberately do NOT call env() — they read the resolved config
 * values as the application sees them when no overriding env is present,
 * mirroring what happens on a server where TWILIO_* keys are absent.
 */
class MessagingConfigDefaultsTest extends TestCase
{
    public function test_otp_driver_default_is_log(): void
    {
        $this->assertSame(
            'log',
            config('eventaat-notifications.otp.driver'),
            'OTP_DRIVER must default to "log" to prevent accidental live SMS on fresh deployments.'
        );
    }

    public function test_notification_driver_default_is_dry_run(): void
    {
        $this->assertSame(
            'dry_run',
            config('eventaat-notifications.booking_notifications.driver'),
            'NOTIFICATION_DRIVER must default to "dry_run" to prevent accidental live booking SMS on fresh deployments.'
        );
    }

    public function test_twilio_account_sid_default_is_empty(): void
    {
        $this->assertSame(
            '',
            config('eventaat-notifications.twilio.account_sid'),
            'TWILIO_ACCOUNT_SID must be empty by default (set only when using twilio_sms or twilio_whatsapp).'
        );
    }

    public function test_twilio_auth_token_default_is_empty(): void
    {
        $this->assertSame(
            '',
            config('eventaat-notifications.twilio.auth_token'),
            'TWILIO_AUTH_TOKEN must be empty by default.'
        );
    }

    public function test_twilio_messaging_service_sid_default_is_empty(): void
    {
        $this->assertSame(
            '',
            config('eventaat-notifications.twilio.messaging_service_sid'),
            'TWILIO_MESSAGING_SERVICE_SID must be empty by default.'
        );
    }

    public function test_twilio_whatsapp_from_default_is_empty(): void
    {
        $this->assertSame(
            '',
            config('eventaat-notifications.twilio.whatsapp.from'),
            'TWILIO_WHATSAPP_FROM must be empty by default (WhatsApp OTP blocked until template approved).'
        );
    }

    public function test_twilio_whatsapp_otp_content_sid_default_is_empty(): void
    {
        $this->assertSame(
            '',
            config('eventaat-notifications.twilio.whatsapp.otp_content_sid'),
            'TWILIO_WHATSAPP_OTP_CONTENT_SID must be empty by default.'
        );
    }

    public function test_otp_validity_period_default_is_300(): void
    {
        $this->assertSame(
            300,
            config('eventaat-notifications.twilio.otp_validity_period'),
            'TWILIO_OTP_VALIDITY_PERIOD default must be 300 seconds.'
        );
    }

    public function test_notification_validity_period_default_is_36000(): void
    {
        $this->assertSame(
            36000,
            config('eventaat-notifications.twilio.notification_validity_period'),
            'TWILIO_NOTIFICATION_VALIDITY_PERIOD default must be 36000 seconds.'
        );
    }

    public function test_otp_rate_limit_defaults_are_safe(): void
    {
        $this->assertSame(60, config('eventaat-notifications.otp_rate_limit.request_cooldown_seconds'));
        $this->assertSame(5, config('eventaat-notifications.otp_rate_limit.request_max_per_hour'));
        $this->assertSame(5, config('eventaat-notifications.otp_rate_limit.verify_max_attempts'));
        $this->assertSame(10, config('eventaat-notifications.otp_rate_limit.verify_decay_minutes'));
    }
}
