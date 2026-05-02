<?php

namespace App\Services\Notifications\Providers;

use App\Models\BookingNotification;

class InternalDryRunNotificationProvider implements NotificationProvider
{
    public const PROVIDER_NAME = 'internal_dry_run';

    public function identifier(): string
    {
        return self::PROVIDER_NAME;
    }

    public function send(BookingNotification $notification): NotificationProviderResult
    {
        // Intentionally no external calls in Phase 10A.
        return NotificationProviderResult::success();
    }
}
