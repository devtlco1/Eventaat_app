<?php

namespace App\Http\Resources\Mobile;

use App\Models\RestaurantStory;
use App\Models\RestaurantStoryItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin RestaurantStory
 */
class MobileStoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Prefer first item's media URL over the legacy media_url field.
        $firstItem = $this->relationLoaded('items')
            ? $this->items->first()
            : null;

        $mediaUrl = $this->resolveMediaUrl($firstItem);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'story_type' => $this->story_type,
            'status' => $this->status,
            'media_url' => $mediaUrl,
            'thumbnail_url' => $mediaUrl,
            'display_order' => (int) ($this->display_order ?? 0),
        ];
    }

    private function resolveMediaUrl(?RestaurantStoryItem $item): ?string
    {
        // Item with a storage media_path takes priority.
        if ($item !== null && filled($item->media_path)) {
            return Storage::disk('public')->url($item->media_path);
        }

        // Legacy direct URL field (already a full URL, not a storage path).
        if (filled($this->media_url)) {
            return $this->media_url;
        }

        return null;
    }
}
