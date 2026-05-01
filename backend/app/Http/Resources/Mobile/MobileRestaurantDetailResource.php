<?php

namespace App\Http\Resources\Mobile;

use App\Models\Branch;
use App\Models\BranchAvailabilityRule;
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
                return $this->branches->map(function (Branch $branch) {
                    return [
                        'id' => $branch->id,
                        'name' => $branch->name,
                        'code' => $branch->code,
                        'booking_availability' => self::bookingAvailabilityForBranch($branch),
                        'seating_areas' => MobileSeatingAreaResource::collection($branch->seatingAreas),
                    ];
                })->values();
            }),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function bookingAvailabilityForBranch(Branch $branch): ?array
    {
        /** @var BranchAvailabilityRule|null $rule */
        $rule = $branch->relationLoaded('availabilityRule')
            ? $branch->availabilityRule
            : null;

        if ($rule === null) {
            return null;
        }

        return [
            'is_booking_enabled' => (bool) $rule->is_booking_enabled,
            'booking_duration_minutes' => (int) $rule->booking_duration_minutes,
            'min_advance_minutes' => (int) $rule->min_advance_minutes,
            'max_advance_days' => (int) $rule->max_advance_days,
            'open_time' => self::nullableTimeString($rule->open_time),
            'close_time' => self::nullableTimeString($rule->close_time),
            'mon' => (bool) $rule->mon,
            'tue' => (bool) $rule->tue,
            'wed' => (bool) $rule->wed,
            'thu' => (bool) $rule->thu,
            'fri' => (bool) $rule->fri,
            'sat' => (bool) $rule->sat,
            'sun' => (bool) $rule->sun,
        ];
    }

    private static function nullableTimeString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
