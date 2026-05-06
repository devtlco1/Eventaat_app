<?php

namespace App\Http\Resources\Mobile;

use App\Models\RestaurantOffer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RestaurantOffer
 */
class MobileOfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'offer_type' => $this->offer_type,
            'discount_value' => $this->discount_value,
            'status' => $this->status,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'image_url' => null,
            'terms' => $this->terms,
        ];
    }
}
