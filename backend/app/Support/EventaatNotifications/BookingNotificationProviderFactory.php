<?php

namespace App\Support\EventaatNotifications;

use App\Exceptions\UnsupportedNotificationDriverException;
use App\Services\Notifications\Providers\InternalDryRunNotificationProvider;
use App\Services\Notifications\Providers\NotificationProvider;

final class BookingNotificationProviderFactory
{
    /**
     * Resolve the booking notification provider for NOTIFICATION_DRIVER.
     */
    public static function make(?string $driver = null): NotificationProvider
    {
        $driver ??= (string) config('eventaat-notifications.booking_notifications.driver', 'dry_run');
        $driver = strtolower(str_replace('-', '_', trim($driver)));

        if ($driver === '') {
            throw UnsupportedNotificationDriverException::forDriver('');
        }

        return match ($driver) {
            'dry_run' => new InternalDryRunNotificationProvider,
            'sms', 'whatsapp' => throw UnsupportedNotificationDriverException::forDriver($driver),
            default => throw UnsupportedNotificationDriverException::forDriver($driver),
        };
    }
}
