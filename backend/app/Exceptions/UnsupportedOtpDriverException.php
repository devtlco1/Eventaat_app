<?php

namespace App\Exceptions;

use RuntimeException;

final class UnsupportedOtpDriverException extends RuntimeException
{
    public static function forDriver(string $driver): self
    {
        return new self(sprintf(
            'Unsupported OTP_DRIVER [%s]. Use OTP_DRIVER=log for local/dev, or OTP_DRIVER=twilio_sms with TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN, and TWILIO_MESSAGING_SERVICE_SID for SMS. Values sms and whatsapp are reserved and not implemented.',
            $driver === '' ? '(empty)' : $driver,
        ));
    }
}
