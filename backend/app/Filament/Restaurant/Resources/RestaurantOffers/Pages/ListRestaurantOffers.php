<?php

namespace App\Filament\Restaurant\Resources\RestaurantOffers\Pages;

use App\Filament\Restaurant\Resources\RestaurantOffers\RestaurantOfferResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRestaurantOffers extends ListRecords
{
    protected static string $resource = RestaurantOfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

