<?php

namespace App\Filament\Platform\Resources\RestaurantSubscriptions\Pages;

use App\Filament\Platform\Resources\RestaurantSubscriptions\RestaurantSubscriptionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRestaurantSubscription extends ViewRecord
{
    protected static string $resource = RestaurantSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
