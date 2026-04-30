<?php

namespace App\Filament\Platform\Resources\Bookings\Pages;

use App\Filament\Platform\Resources\Bookings\BookingResource;
use Filament\Resources\Pages\ListRecords;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
