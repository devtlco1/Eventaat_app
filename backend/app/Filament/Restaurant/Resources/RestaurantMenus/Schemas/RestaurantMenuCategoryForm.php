<?php

namespace App\Filament\Restaurant\Resources\RestaurantMenus\Schemas;

use App\Filament\Support\FilamentSchemaLayout;
use App\Filament\Support\RestaurantMenuCategoryFormSchema;
use Filament\Schemas\Schema;

class RestaurantMenuCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return FilamentSchemaLayout::stackSections($schema)->components(RestaurantMenuCategoryFormSchema::sections());
    }
}
