<?php

namespace App\Filament\Platform\Resources\RestaurantMenus\Schemas;

use App\Filament\Support\RestaurantMenuCategoryFormSchema;
use Filament\Schemas\Schema;

class RestaurantMenuCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(RestaurantMenuCategoryFormSchema::sections());
    }
}
