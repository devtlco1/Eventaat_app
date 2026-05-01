<?php

namespace App\Filament\Platform\Resources\RestaurantEvents\Pages;

use App\Filament\Platform\Resources\RestaurantEvents\RestaurantEventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRestaurantEvents extends ListRecords
{
    protected static string $resource = RestaurantEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

