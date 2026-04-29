<?php

namespace App\Filament\Platform\Resources\Restaurants\Pages;

use App\Filament\Platform\Resources\Restaurants\RestaurantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRestaurant extends CreateRecord
{
    protected static string $resource = RestaurantResource::class;
}
