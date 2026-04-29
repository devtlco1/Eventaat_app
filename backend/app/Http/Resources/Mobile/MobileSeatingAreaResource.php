<?php

namespace App\Http\Resources\Mobile;

use App\Models\SeatingArea;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SeatingArea
 */
class MobileSeatingAreaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type?->value,
            'tables' => MobileRestaurantTableResource::collection($this->whenLoaded('tables')),
        ];
    }
}

