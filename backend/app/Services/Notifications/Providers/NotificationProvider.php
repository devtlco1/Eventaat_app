<?php

namespace App\Services\Notifications\Providers;

use App\Models\BookingNotification;

interface NotificationProvider
{
    /**
     * Stable identifier stored on dispatch attempts (e.g. internal_dry_run).
     */
    public function identifier(): string;

    public function send(BookingNotification $notification): NotificationProviderResult;
}
