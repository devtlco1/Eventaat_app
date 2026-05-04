<?php

namespace App\Support\EventaatNotifications;

use App\Exceptions\UnsupportedNotificationDriverException;
use App\Services\Notifications\MessagingSettingsService;
use App\Services\Notifications\Providers\InternalDryRunNotificationProvider;
use App\Services\Notifications\Providers\NotificationProvider;
use App\Services\Notifications\Providers\TwilioSmsNotificationProvider;

final class BookingNotificationProviderFactory
{
    /**
     * Resolve the booking notification provider using MessagingSettingsService (DB → env → config).
     *
     * When $driver is passed explicitly (e.g. from tests) that value is used as-is,
     * bypassing the service. This preserves the existing test-isolation pattern.
     */
    public static function make(?string $driver = null): NotificationProvider
    {
        if ($driver === null) {
            $driver = app(MessagingSettingsService::class)->effectiveNotificationDriver();
        }

        $driver = strtolower(str_replace('-', '_', trim($driver)));

        if ($driver === '') {
            throw UnsupportedNotificationDriverException::forDriver('');
        }

        return match ($driver) {
            'dry_run' => new InternalDryRunNotificationProvider,
            'twilio_sms' => new TwilioSmsNotificationProvider,
            default => throw UnsupportedNotificationDriverException::forDriver($driver),
        };
    }
}
