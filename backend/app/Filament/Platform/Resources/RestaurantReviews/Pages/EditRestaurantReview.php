<?php

namespace App\Filament\Platform\Resources\RestaurantReviews\Pages;

use App\Filament\Platform\Resources\RestaurantReviews\RestaurantReviewResource;
use Filament\Resources\Pages\EditRecord;

class EditRestaurantReview extends EditRecord
{
    protected static string $resource = RestaurantReviewResource::class;
}
