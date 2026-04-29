<?php

namespace App\Filament\Restaurant\Resources\SeatingAreas\Pages;

use App\Filament\Restaurant\Resources\SeatingAreas\SeatingAreaResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSeatingArea extends ViewRecord
{
    protected static string $resource = SeatingAreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
