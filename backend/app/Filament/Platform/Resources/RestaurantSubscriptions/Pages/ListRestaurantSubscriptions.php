<?php

namespace App\Filament\Platform\Resources\RestaurantSubscriptions\Pages;

use App\Filament\Platform\Resources\RestaurantSubscriptions\RestaurantSubscriptionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRestaurantSubscriptions extends ListRecords
{
    protected static string $resource = RestaurantSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
