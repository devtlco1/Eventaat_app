<?php

namespace App\Filament\Platform\Resources\RestaurantMenus\Pages;

use App\Filament\Platform\Resources\RestaurantMenus\RestaurantMenuCategoryResource;
use Filament\Resources\Pages\Concerns\InteractsWithParentRecord;
use Filament\Resources\Pages\EditRecord;

class EditRestaurantMenuCategory extends EditRecord
{
    use InteractsWithParentRecord;

    protected static string $resource = RestaurantMenuCategoryResource::class;
}
