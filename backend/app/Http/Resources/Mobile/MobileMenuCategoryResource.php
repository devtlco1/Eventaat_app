<?php

namespace App\Http\Resources\Mobile;

use App\Models\RestaurantMenuCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RestaurantMenuCategory
 */
class MobileMenuCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_order' => (int) ($this->display_order ?? 0),
            'items' => MobileMenuItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
