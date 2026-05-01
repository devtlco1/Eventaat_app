<?php

namespace App\Filament\Restaurant\Resources\RestaurantEvents\Pages;

use App\Filament\Restaurant\Resources\RestaurantEvents\RestaurantEventResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRestaurantEvent extends ViewRecord
{
    protected static string $resource = RestaurantEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

