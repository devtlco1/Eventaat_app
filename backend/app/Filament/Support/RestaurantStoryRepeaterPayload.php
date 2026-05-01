<?php

namespace App\Filament\Support;

use App\Models\RestaurantStoryItem;

final class RestaurantStoryRepeaterPayload
{
    /**
     * Rows that Filament would persist (complete slide payloads).
     *
     * @param  array<mixed>  $itemsState
     */
    public static function countPersistableRows(array $itemsState): int
    {
        $count = 0;
        foreach ($itemsState as $row) {
            if (! is_array($row)) {
                continue;
            }
            if (self::normalizeRowForPersistence($row) !== null) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null Null when the row should not be saved (empty/incomplete draft noise).
     */
    public static function normalizeRowForPersistence(array $data): ?array
    {
        $type = $data['item_type'] ?? null;
        if (! in_array($type, RestaurantStoryItem::ITEM_TYPES, true)) {
            return null;
        }

        $mediaPath = $data['media_path'] ?? null;
        if (is_array($mediaPath)) {
            $mediaPath = filled($mediaPath) ? reset($mediaPath) : null;
        }
        $body = $data['body'] ?? null;
        $sortOrder = $data['sort_order'] ?? 0;
        $duration = $data['item_duration_seconds'] ?? null;
        $ctaLabel = $data['cta_label'] ?? null;
        $ctaUrl = $data['cta_url'] ?? null;

        if ($duration !== null && $duration !== '') {
            $duration = (int) $duration;
            if ($duration < 1) {
                return null;
            }
        } else {
            $duration = null;
        }

        if (in_array($type, [RestaurantStoryItem::TYPE_IMAGE, RestaurantStoryItem::TYPE_VIDEO], true)) {
            if (! filled($mediaPath)) {
                return null;
            }

            return [
                'item_type' => $type,
                'media_path' => $mediaPath,
                'body' => null,
                'sort_order' => (int) $sortOrder,
                'item_duration_seconds' => $duration,
                'cta_label' => filled($ctaLabel) ? $ctaLabel : null,
                'cta_url' => filled($ctaUrl) ? $ctaUrl : null,
            ];
        }

        if ($type === RestaurantStoryItem::TYPE_TEXT) {
            if (! filled($body)) {
                return null;
            }

            return [
                'item_type' => $type,
                'media_path' => null,
                'body' => $body,
                'sort_order' => (int) $sortOrder,
                'item_duration_seconds' => $duration,
                'cta_label' => filled($ctaLabel) ? $ctaLabel : null,
                'cta_url' => filled($ctaUrl) ? $ctaUrl : null,
            ];
        }

        return null;
    }
}
