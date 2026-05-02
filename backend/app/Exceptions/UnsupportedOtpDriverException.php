<?php

namespace App\Exceptions;

use RuntimeException;

final class UnsupportedOtpDriverException extends RuntimeException
{
    public static function forDriver(string $driver): self
    {
        return new self(sprintf(
            'Unsupported OTP_DRIVER [%s]. Use OTP_DRIVER=log for local/dev. Planned drivers (not implemented yet): sms, whatsapp.',
            $driver === '' ? '(empty)' : $driver,
        ));
    }
}
