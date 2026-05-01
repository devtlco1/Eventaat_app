<?php

namespace App\Filament\Restaurant\Resources\RestaurantStories\Pages;

use App\Filament\Restaurant\Resources\RestaurantStories\RestaurantStoryResource;
use App\Filament\Support\RestaurantStoryLifecycle;
use App\Models\RestaurantStory;
use Filament\Resources\Pages\EditRecord;

class EditRestaurantStory extends EditRecord
{
    use RestaurantStoryLifecycle;

    protected static string $resource = RestaurantStoryResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var RestaurantStory $record */
        $record = $this->getRecord();
        $record->refresh();

        return $this->applyRestaurantStoryLifetimeFields($data, $record);
    }
}
