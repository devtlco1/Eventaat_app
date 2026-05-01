<?php

namespace App\Filament\Platform\Resources\RestaurantEvents\Pages;

use App\Filament\Platform\Resources\RestaurantEvents\RestaurantEventResource;
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

