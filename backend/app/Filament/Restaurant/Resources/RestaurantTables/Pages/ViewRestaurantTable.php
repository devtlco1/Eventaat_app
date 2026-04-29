<?php

namespace App\Filament\Restaurant\Resources\RestaurantTables\Pages;

use App\Filament\Restaurant\Resources\RestaurantTables\RestaurantTableResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRestaurantTable extends ViewRecord
{
    protected static string $resource = RestaurantTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
