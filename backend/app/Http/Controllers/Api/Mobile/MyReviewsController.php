<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\MobileReviewResource;
use App\Models\RestaurantReview;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MyReviewsController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 50));

        $query = RestaurantReview::query()
            ->where('user_id', $user->id)
            ->with(['restaurant', 'branch'])
            ->orderByDesc('created_at');

        return MobileReviewResource::collection(
            $query->paginate($perPage)->withQueryString()
        );
    }

    public function show(Request $request, RestaurantReview $review): MobileReviewResource
    {
        /** @var User $user */
        $user = $request->user();

        if ($review->user_id !== $user->id) {
            abort(404);
        }

        $review->load(['restaurant', 'branch']);

        return new MobileReviewResource($review);
    }
}
