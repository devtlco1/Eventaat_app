<?php

namespace App\Filament\Platform\Resources\RestaurantTables\Pages;

use App\Filament\Platform\Resources\RestaurantTables\RestaurantTableResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRestaurantTable extends CreateRecord
{
    protected static string $resource = RestaurantTableResource::class;
}
