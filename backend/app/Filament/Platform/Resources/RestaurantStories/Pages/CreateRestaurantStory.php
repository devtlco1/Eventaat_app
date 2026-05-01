<?php

namespace App\Filament\Platform\Resources\RestaurantStories\Pages;

use App\Filament\Platform\Resources\RestaurantStories\RestaurantStoryResource;
use App\Filament\Support\RestaurantStoryFormItemsWorkflowRules;
use App\Filament\Support\RestaurantStoryLifecycle;
use App\Models\RestaurantStory;
use Filament\Resources\Pages\CreateRecord;

class CreateRestaurantStory extends CreateRecord
{
    use RestaurantStoryFormItemsWorkflowRules;
    use RestaurantStoryLifecycle;

    protected static string $resource = RestaurantStoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $status = $data['status'] ?? RestaurantStory::STATUS_DRAFT;
        $this->validateStoryItemsForWorkflow($status);

        if (in_array($status, [
            RestaurantStory::STATUS_PENDING_REVIEW,
            RestaurantStory::STATUS_PUBLISHED,
        ], true)) {
            $data['_story_items_pending_valid'] = true;
        }

        $stub = new RestaurantStory([
            'status' => $status,
            'lifetime_mode' => $data['lifetime_mode'] ?? RestaurantStory::LIFETIME_24H,
            'lifetime_hours' => $data['lifetime_hours'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ]);

        $result = $this->applyRestaurantStoryLifetimeFields($data, $stub);
        unset($result['_story_items_pending_valid']);

        return $result;
    }
}
