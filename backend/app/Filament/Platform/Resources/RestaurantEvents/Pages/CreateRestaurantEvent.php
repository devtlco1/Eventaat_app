<?php

namespace App\Filament\Platform\Resources\RestaurantEvents\Pages;

use App\Filament\Platform\Resources\RestaurantEvents\RestaurantEventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRestaurantEvent extends CreateRecord
{
    protected static string $resource = RestaurantEventResource::class;
}

