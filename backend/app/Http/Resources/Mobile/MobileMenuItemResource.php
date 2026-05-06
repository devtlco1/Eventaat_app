<?php

namespace App\Http\Resources\Mobile;

use App\Models\RestaurantMenuItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin RestaurantMenuItem
 */
class MobileMenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imagePath = RestaurantMenuItem::normalizeStoredImagePath($this->image_path);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'currency' => $this->currency,
            'image_url' => $imagePath
                ? Storage::disk('public')->url($imagePath)
                : null,
            'is_available' => (bool) $this->is_available,
            'is_featured' => (bool) $this->is_featured,
            'display_order' => (int) ($this->display_order ?? 0),
        ];
    }
}
