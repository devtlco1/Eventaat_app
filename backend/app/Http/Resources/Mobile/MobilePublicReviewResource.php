<?php

namespace App\Http\Resources\Mobile;

use App\Models\RestaurantReview;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RestaurantReview
 */
class MobilePublicReviewResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $displayName = filled($this->customer_name) ? (string) $this->customer_name : 'Customer';

        return [
            'id' => $this->id,
            'customer_name' => $displayName,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
