<?php

namespace App\Support\EventaatNotifications;

use App\Exceptions\UnsupportedOtpDriverException;
use App\Services\Otp\LocalLogOtpSender;
use App\Services\Otp\OtpSender;
use App\Services\Otp\TwilioSmsOtpSender;
use App\Services\Otp\TwilioWhatsAppOtpSender;

final class OtpSenderFactory
{
    /**
     * Resolve the OTP sender for the configured driver (OTP_DRIVER).
     */
    public static function make(?string $driver = null): OtpSender
    {
        $driver ??= (string) config('eventaat-notifications.otp.driver', 'log');
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
