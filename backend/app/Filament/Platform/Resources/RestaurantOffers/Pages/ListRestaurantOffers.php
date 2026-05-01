<?php

namespace App\Filament\Platform\Resources\RestaurantOffers\Pages;

use App\Filament\Platform\Resources\RestaurantOffers\RestaurantOfferResource;
use Filament\Resources\Pages\ListRecords;

class ListRestaurantOffers extends ListRecords
{
    protected static string $resource = RestaurantOfferResource::class;
}

