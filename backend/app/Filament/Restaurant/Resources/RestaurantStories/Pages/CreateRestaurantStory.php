<?php

namespace App\Filament\Restaurant\Resources\RestaurantStories\Pages;

use App\Filament\Restaurant\Resources\RestaurantStories\RestaurantStoryResource;
use App\Filament\Support\RestaurantStoryLifecycle;
use App\Models\RestaurantStory;
use Filament\Resources\Pages\CreateRecord;

class CreateRestaurantStory extends CreateRecord
{
    use RestaurantStoryLifecycle;

    protected static string $resource = RestaurantStoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $stub = new RestaurantStory([
            'status' => $data['status'] ?? RestaurantStory::STATUS_DRAFT,
            'lifetime_mode' => $data['lifetime_mode'] ?? RestaurantStory::LIFETIME_24H,
            'lifetime_hours' => $data['lifetime_hours'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ]);

        return $this->applyRestaurantStoryLifetimeFields($data, $stub);
    }
}
