<?php

namespace App\Services\Notifications\Providers;

use App\Models\BookingNotification;

interface NotificationProvider
{
    public function send(BookingNotification $notification): NotificationProviderResult;
}

