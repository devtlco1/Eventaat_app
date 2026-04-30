<?php

namespace App\Filament\Platform\Resources\Bookings\Pages;

use App\Filament\Platform\Resources\Bookings\BookingResource;
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
