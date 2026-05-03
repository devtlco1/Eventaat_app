<?php

namespace App\Exceptions;

use RuntimeException;

final class UnsupportedOtpDriverException extends RuntimeException
{
    public static function forDriver(string $driver): self
    {
        return new self(sprintf(
            'Unsupported OTP_DRIVER [%s]. Supported: log, twilio_sms, twilio_whatsapp. SMS requires TWILIO_MESSAGING_SERVICE_SID; WhatsApp requires TWILIO_WHATSAPP_FROM and TWILIO_WHATSAPP_OTP_CONTENT_SID (approved Content Template). Values sms and whatsapp alone are reserved.',
            $driver === '' ? '(empty)' : $driver,
        ));
    }
}
