<?php

namespace App\Exceptions;

use RuntimeException;

final class UnsupportedNotificationDriverException extends RuntimeException
{
    public static function forDriver(string $driver): self
    {
        return new self(sprintf(
            'Unsupported NOTIFICATION_DRIVER [%s]. Use NOTIFICATION_DRIVER=dry_run for local/dev. Planned drivers (not implemented yet): sms, whatsapp.',
            $driver === '' ? '(empty)' : $driver,
        ));
    }
}
