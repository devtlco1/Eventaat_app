<?php

namespace App\Support\EventaatNotifications;

use App\Exceptions\UnsupportedOtpDriverException;
use App\Services\Notifications\MessagingSettingsService;
use App\Services\Otp\LocalLogOtpSender;
use App\Services\Otp\OtpSender;
use App\Services\Otp\TwilioSmsOtpSender;
use App\Services\Otp\TwilioWhatsAppOtpSender;

final class OtpSenderFactory
{
    /**
     * Resolve the OTP sender using MessagingSettingsService (DB → env → config).
     *
     * When $driver is passed explicitly (e.g. from tests) that value is used as-is,
     * bypassing the service. This preserves the existing test-isolation pattern.
     */
    public static function make(?string $driver = null): OtpSender
    {
        if ($driver === null) {
            $driver = app(MessagingSettingsService::class)->effectiveOtpDriver();
        }

        $driver = strtolower(trim($driver));

        if ($driver === '') {
            throw UnsupportedOtpDriverException::forDriver('');
        }

        return match ($driver) {
            'log' => new LocalLogOtpSender,
            'twilio_sms' => new TwilioSmsOtpSender,
            'twilio_whatsapp' => new TwilioWhatsAppOtpSender,
            'sms', 'whatsapp' => throw UnsupportedOtpDriverException::forDriver($driver),
            default => throw UnsupportedOtpDriverException::forDriver($driver),
        };
    }
}
