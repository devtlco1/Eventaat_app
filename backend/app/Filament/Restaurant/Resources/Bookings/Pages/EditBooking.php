<?php

namespace App\Filament\Restaurant\Resources\Bookings\Pages;

use App\Filament\Restaurant\Resources\Bookings\BookingResource;
use Filament\Resources\Pages\EditRecord;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
