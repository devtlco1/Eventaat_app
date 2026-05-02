<?php

namespace App\Filament\Restaurant\Resources\RestaurantMenus\Pages;

use App\Filament\Restaurant\Resources\RestaurantMenus\RestaurantMenuResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateRestaurantMenu extends CreateRecord
{
    protected static string $resource = RestaurantMenuResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;
}
