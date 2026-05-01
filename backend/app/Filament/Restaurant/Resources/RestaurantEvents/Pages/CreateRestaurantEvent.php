<?php

namespace App\Filament\Restaurant\Resources\RestaurantEvents\Pages;

use App\Filament\Restaurant\Resources\RestaurantEvents\RestaurantEventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRestaurantEvent extends CreateRecord
{
    protected static string $resource = RestaurantEventResource::class;
}

