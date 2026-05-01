<?php

namespace App\Filament\Platform\Resources\RestaurantReviews\Pages;

use App\Filament\Platform\Resources\RestaurantReviews\RestaurantReviewResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRestaurantReview extends CreateRecord
{
    protected static string $resource = RestaurantReviewResource::class;
}
