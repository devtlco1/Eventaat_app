<?php

namespace App\Filament\Platform\Resources\RestaurantReviews\Pages;

use App\Filament\Platform\Resources\RestaurantReviews\RestaurantReviewResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRestaurantReviews extends ListRecords
{
    protected static string $resource = RestaurantReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
