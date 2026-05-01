<?php

namespace App\Filament\Platform\Resources\RestaurantMenus\Pages;

use App\Filament\Platform\Resources\RestaurantMenus\RestaurantMenuResource;
use Filament\Resources\Pages\ListRecords;

class ListRestaurantMenus extends ListRecords
{
    protected static string $resource = RestaurantMenuResource::class;
}
