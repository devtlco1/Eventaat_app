<?php

namespace App\Filament\Restaurant\Resources\RestaurantReviews\Pages;

use App\Filament\Restaurant\Resources\RestaurantReviews\RestaurantReviewResource;
use Filament\Resources\Pages\ListRecords;

class ListRestaurantReviews extends ListRecords
{
    protected static string $resource = RestaurantReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
