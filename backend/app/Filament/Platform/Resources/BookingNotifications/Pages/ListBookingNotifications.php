<?php

namespace App\Filament\Platform\Resources\BookingNotifications\Pages;

use App\Filament\Platform\Resources\BookingNotifications\BookingNotificationResource;
use Filament\Resources\Pages\ListRecords;

class ListBookingNotifications extends ListRecords
{
    protected static string $resource = BookingNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
