<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\RestaurantStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\MobilePublicReviewResource;
use App\Models\Restaurant;
use App\Models\RestaurantReview;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RestaurantReviewController extends Controller
{
    public function index(Request $request, Restaurant $restaurant): AnonymousResourceCollection
    {
        if ($restaurant->status?->value !== RestaurantStatus::Active->value) {
            abort(404);
        }

        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 50));

        $query = RestaurantReview::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('status', RestaurantReview::STATUS_PUBLISHED)
            ->orderByDesc('created_at');

        return MobilePublicReviewResource::collection(
            $query->paginate($perPage)->withQueryString()
        );
    }
}
