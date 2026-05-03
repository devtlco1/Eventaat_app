<?php

namespace App\Exceptions;

use RuntimeException;

final class UnsupportedNotificationDriverException extends RuntimeException
{
    public static function forDriver(string $driver): self
    {
        return new self(sprintf(
            'Unsupported NOTIFICATION_DRIVER [%s]. Supported: dry_run (default), twilio_sms. WhatsApp booking notifications are not implemented.',
            $driver === '' ? '(empty)' : $driver,
        ));
    }
}
