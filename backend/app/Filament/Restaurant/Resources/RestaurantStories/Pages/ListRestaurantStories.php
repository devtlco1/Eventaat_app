<?php

namespace App\Filament\Restaurant\Resources\RestaurantStories\Pages;

use App\Filament\Restaurant\Resources\RestaurantStories\RestaurantStoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRestaurantStories extends ListRecords
{
    protected static string $resource = RestaurantStoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

