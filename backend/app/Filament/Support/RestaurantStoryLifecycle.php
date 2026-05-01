<?php

namespace App\Filament\Support;

use App\Models\RestaurantStory;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

trait RestaurantStoryLifecycle
{
    /**
     * @param  array<string, mixed>|null  $incomingAttributes
     */
    protected function ensureRenderableStoryContent(RestaurantStory $story, ?array $incomingAttributes = null): void
    {
        if (is_array($incomingAttributes) && ($incomingAttributes['_story_items_pending_valid'] ?? false)) {
            return;
        }

        $story->loadMissing('items');

        $merged = $story;

        if (is_array($incomingAttributes)) {
            $merged = $story->replicate();
            $merged->fill($incomingAttributes);
        }

        if ($merged->hasRenderableContent()) {
            return;
        }

        throw ValidationException::withMessages([
            'data.title' => ['Add at least one story slide before publishing.'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function applyRestaurantStoryLifetimeFields(array $data, RestaurantStory $record): array
    {
        $incomingStatus = $data['status'] ?? $record->status;
        $wasPublished = $record->status === RestaurantStory::STATUS_PUBLISHED;
        $willPublish = $incomingStatus === RestaurantStory::STATUS_PUBLISHED;

        $lifetimeMode = $data['lifetime_mode'] ?? $record->lifetime_mode ?? RestaurantStory::LIFETIME_24H;
        $data['lifetime_mode'] = $lifetimeMode;

        if ($lifetimeMode !== RestaurantStory::LIFETIME_MANUAL) {
            $data['lifetime_hours'] = null;
        }

        if ($willPublish || ($wasPublished && ($incomingStatus === RestaurantStory::STATUS_PUBLISHED))) {
            $this->ensureRenderableStoryContent($record, $data);

            $startsAt = $data['starts_at'] ?? $record->starts_at;
            $endsAt = $data['ends_at'] ?? $record->ends_at;

            if ($lifetimeMode !== RestaurantStory::LIFETIME_MANUAL) {
                $stub = new RestaurantStory([
                    'lifetime_mode' => $lifetimeMode,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                ]);

                $stub->applyLifetimeWindowForPublishing();
                $data['starts_at'] = $stub->starts_at;
                $data['ends_at'] = $stub->ends_at;

                return $data;
            }

            if ($endsAt === null) {
                $hours = $data['lifetime_hours'] ?? $record->lifetime_hours;
                if (! $hours || $hours < 1) {
                    throw ValidationException::withMessages([
                        'data.ends_at' => ['Set an end time or provide lifetime hours for manual lifetime mode.'],
                    ]);
                }

                $starts = $startsAt ? Carbon::parse($startsAt) : Carbon::now();
                $data['starts_at'] = $starts;
                $data['ends_at'] = $starts->clone()->addHours((int) $hours);
            }
        }

        return $data;
    }
}
