<?php

namespace App\Filament\Restaurant\Resources\Bookings\Pages;

use App\Filament\Restaurant\Resources\Bookings\BookingResource;
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
