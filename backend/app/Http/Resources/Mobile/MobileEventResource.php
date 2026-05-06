<?php

namespace App\Http\Resources\Mobile;

use App\Models\RestaurantEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RestaurantEvent
 */
class MobileEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $activeReserved = $this->activeReservedSeats();
        $remaining = $this->remainingSeats();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'restaurant' => $this->whenLoaded('restaurant', fn () => [
                'id' => $this->restaurant->id,
                'name' => $this->restaurant->name,
            ]),
            'branch' => $this->whenLoaded('branch', fn () => $this->branch
                ? ['id' => $this->branch->id, 'name' => $this->branch->name]
                : null),
            'status' => $this->status,
            'booking_mode' => $this->booking_mode,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'price_label' => $this->price_label,
            'capacity' => $this->capacity,
            'active_reserved_seats' => $activeReserved,
            'remaining_seats' => $remaining,
            'description' => $this->description,
        ];
    }
}
