<?php

namespace App\Filament\Restaurant\Resources\SeatingAreas\Pages;

use App\Filament\Restaurant\Resources\SeatingAreas\SeatingAreaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSeatingAreas extends ListRecords
{
    protected static string $resource = SeatingAreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
