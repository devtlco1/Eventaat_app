<?php

namespace App\Http\Resources\Mobile;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Restaurant
 */
class MobileRestaurantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'active_branches_count' => (int) ($this->active_branches_count ?? 0),
            'avg_rating' => $this->avg_rating !== null ? round((float) $this->avg_rating, 1) : null,
            'review_count' => (int) ($this->review_count ?? 0),
        ];
    }
}
