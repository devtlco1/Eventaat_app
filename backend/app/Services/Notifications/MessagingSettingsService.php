<?php

namespace App\Services\Notifications;

use App\Models\MessagingSettings;

/**
 * Resolves effective OTP and notification drivers for the current request.
 *
 * Resolution order (highest → lowest priority):
 *  1. external_messaging_enabled kill-switch (overrides everything when false)
 *  2. DB singleton row (MessagingSettings id=1) — null means "use env"
 *  3. env / config default (OTP_DRIVER / NOTIFICATION_DRIVER)
 *
 * The service never returns null; it always resolves to a concrete driver string.
 */
class MessagingSettingsService
{
    /**
     * Resolve the effective OTP driver.
     *
     * Returns 'log' when:
     *  - external_messaging_enabled = false (kill-switch)
     *  - DB otp_driver is null AND config default is 'log'
     *
     * Returns 'twilio_whatsapp' only when whatsapp_otp_enabled = true in DB.
     * If whatsapp_otp_enabled is false and DB says 'twilio_whatsapp', falls back
     * to env/config (treating the DB value as invalid until template approved).
     */
    public function effectiveOtpDriver(): string
    {
        $settings = MessagingSettings::getInstance();

        // Kill-switch: external messaging globally disabled → always log
        if ($settings !== null && ! $settings->external_messaging_enabled) {
            return MessagingSettings::OTP_DRIVER_LOG;
        }

        $dbDriver = $settings?->otp_driver;

        // WhatsApp guard: twilio_whatsapp blocked until template approved
        if (
            $dbDriver === MessagingSettings::OTP_DRIVER_TWILIO_WHATSAPP
            && ! ($settings?->whatsapp_otp_enabled ?? false)
        ) {
            $dbDriver = null; // fall through to env/config
        }

        // Use DB driver if set, otherwise fall back to env/config
        return $dbDriver
            ?? (string) config('eventaat-notifications.otp.driver', MessagingSettings::OTP_DRIVER_LOG);
    }

    /**
     * Resolve the effective booking notification driver.
     *
     * Returns 'dry_run' when:
     *  - external_messaging_enabled = false (kill-switch)
     *  - DB notification_driver is null AND config default is 'dry_run'
     */
    public function effectiveNotificationDriver(): string
    {
        $settings = MessagingSettings::getInstance();

        // Kill-switch: external messaging globally disabled → always dry_run
        if ($settings !== null && ! $settings->external_messaging_enabled) {
            return MessagingSettings::NOTIFICATION_DRIVER_DRY_RUN;
        }

        $dbDriver = $settings?->notification_driver;

        // Use DB driver if set, otherwise fall back to env/config
        return $dbDriver
            ?? (string) config('eventaat-notifications.booking_notifications.driver', MessagingSettings::NOTIFICATION_DRIVER_DRY_RUN);
    }

    /**
     * Returns true if Twilio SMS credentials are present in config.
     * Shows as "yes/no" presence only — never exposes actual values.
     */
    public function hasTwilioSmsConfig(): bool
    {
        return filled(config('eventaat-notifications.twilio.account_sid'))
            && filled(config('eventaat-notifications.twilio.auth_token'))
            && filled(config('eventaat-notifications.twilio.messaging_service_sid'));
    }

    /**
     * Returns true if Twilio WhatsApp credentials are present in config.
     * Shows as "yes/no" presence only — never exposes actual values.
     */
    public function hasTwilioWhatsAppConfig(): bool
    {
        return $this->hasTwilioSmsConfig()
            && filled(config('eventaat-notifications.twilio.whatsapp.from'))
            && filled(config('eventaat-notifications.twilio.whatsapp.otp_content_sid'));
    }
}
