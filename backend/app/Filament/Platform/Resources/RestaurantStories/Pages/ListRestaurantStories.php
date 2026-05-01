<?php

namespace App\Filament\Platform\Resources\RestaurantStories\Pages;

use App\Filament\Platform\Resources\RestaurantStories\RestaurantStoryResource;
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

