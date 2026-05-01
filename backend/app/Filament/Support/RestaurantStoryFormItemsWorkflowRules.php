<?php

namespace App\Filament\Support;

use App\Models\RestaurantStory;
use Illuminate\Validation\ValidationException;

trait RestaurantStoryFormItemsWorkflowRules
{
    protected function validateStoryItemsForWorkflow(?string $status): void
    {
        $status ??= RestaurantStory::STATUS_DRAFT;

        $requiresSlides = in_array($status, [
            RestaurantStory::STATUS_PENDING_REVIEW,
            RestaurantStory::STATUS_PUBLISHED,
        ], true);

        if (! $requiresSlides) {
            return;
        }

        /** @var array<string, mixed> $livewireData */
        $livewireData = $this->data ?? [];
        $items = data_get($livewireData, 'items', []);

        if (! is_array($items)) {
            $items = [];
        }

        $count = RestaurantStoryRepeaterPayload::countPersistableRows($items);

        if ($count < 1) {
            throw ValidationException::withMessages([
                'data.items' => ['Add at least one story slide before submitting or publishing.'],
            ]);
        }
    }
}
