<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Booking
 */
class MobileBookingResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status?->value,
            'starts_at' => optional($this->starts_at)->toISOString(),
            'party_size' => $this->party_size,
            'customer_note' => $this->customer_note,
            'restaurant_note' => $this->restaurant_note,
            'accepted_at' => optional($this->accepted_at)->toISOString(),
            'rejected_at' => optional($this->rejected_at)->toISOString(),
            'cancelled_at' => optional($this->cancelled_at)->toISOString(),
            'arrived_at' => optional($this->arrived_at)->toISOString(),
            'seated_at' => optional($this->seated_at)->toISOString(),
            'completed_at' => optional($this->completed_at)->toISOString(),
            'no_show_at' => optional($this->no_show_at)->toISOString(),
            'restaurant' => $this->whenLoaded('restaurant', fn () => [
                'id' => $this->restaurant->id,
                'name' => $this->restaurant->name,
                'slug' => $this->restaurant->slug,
            ]),
            'branch' => $this->whenLoaded('branch', fn () => [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
                'code' => $this->branch->code,
            ]),
            'seating_area' => $this->whenLoaded('seatingArea', fn () => $this->seatingArea ? [
                'id' => $this->seatingArea->id,
                'name' => $this->seatingArea->name,
                'code' => $this->seatingArea->code,
                'type' => $this->seatingArea->type?->value,
            ] : null),
            'table' => $this->whenLoaded('table', fn () => $this->table ? [
                'id' => $this->table->id,
                'label' => $this->table->label,
                'capacity' => $this->table->capacity,
            ] : null),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}

