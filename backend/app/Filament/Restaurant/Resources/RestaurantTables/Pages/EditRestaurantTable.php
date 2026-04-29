<?php

namespace App\Filament\Restaurant\Resources\RestaurantTables\Pages;

use App\Filament\Restaurant\Resources\RestaurantTables\RestaurantTableResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRestaurantTable extends EditRecord
{
    protected static string $resource = RestaurantTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
