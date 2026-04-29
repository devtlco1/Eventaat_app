<?php

namespace App\Filament\Restaurant\Resources\SeatingAreas\Pages;

use App\Filament\Restaurant\Resources\SeatingAreas\SeatingAreaResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSeatingArea extends EditRecord
{
    protected static string $resource = SeatingAreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
