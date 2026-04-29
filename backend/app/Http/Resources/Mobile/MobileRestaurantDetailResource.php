<?php

namespace App\Http\Resources\Mobile;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Restaurant
 */
class MobileRestaurantDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'branches' => $this->whenLoaded('branches', function () {
                return $this->branches->map(function ($branch) {
                    return [
                        'id' => $branch->id,
                        'name' => $branch->name,
                        'code' => $branch->code,
                        'seating_areas' => MobileSeatingAreaResource::collection($branch->seatingAreas),
                    ];
                })->values();
            }),
        ];
    }
}

