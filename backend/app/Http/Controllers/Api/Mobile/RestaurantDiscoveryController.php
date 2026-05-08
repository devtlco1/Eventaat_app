<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\BranchStatus;
use App\Enums\RestaurantStatus;
use App\Enums\TableStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\MobileRestaurantDetailResource;
use App\Http\Resources\Mobile\MobileRestaurantResource;
use App\Models\Restaurant;
use App\Models\RestaurantReview;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RestaurantDiscoveryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $q = trim((string) $request->query('q', ''));
        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 50));

        $publishedReviews = fn ($q) => $q->where('status', RestaurantReview::STATUS_PUBLISHED);

        $query = Restaurant::query()
            ->where('status', RestaurantStatus::Active->value)
            ->withCount([
                'branches as active_branches_count' => fn ($q) => $q->where('status', BranchStatus::Active->value),
                'reviews as review_count' => $publishedReviews,
            ])
            ->withAvg(['reviews as avg_rating' => $publishedReviews], 'rating')
            ->orderBy('name');

        if ($q !== '') {
            // Portable case-insensitive search (works in Postgres + SQLite tests).
            $needle = mb_strtolower($q);
            $query->whereRaw('lower(name) like ?', ['%'.$needle.'%']);
        }

        return MobileRestaurantResource::collection(
            $query->paginate($perPage)->withQueryString()
        );
    }

    public function show(Restaurant $restaurant): MobileRestaurantDetailResource
    {
        // Route binding uses slug, but we still enforce "active" explicitly.
        if ($restaurant->status?->value !== RestaurantStatus::Active->value) {
            abort(404);
        }

        $publishedReviews = fn ($q) => $q->where('status', RestaurantReview::STATUS_PUBLISHED);
        $restaurant->loadCount(['reviews as review_count' => $publishedReviews]);
        $restaurant->loadAvg(['reviews as avg_rating' => $publishedReviews], 'rating');

        $restaurant->load([
            'branches' => function ($q) {
                $q->where('status', BranchStatus::Active->value)
                    ->orderBy('name')
                    ->with([
                        'availabilityRule',
                        'seatingAreas' => function ($q) {
                            $q->where('status', 'active')
                                ->orderBy('name')
                                ->with([
                                    'tables' => fn ($q) => $q->where('status', TableStatus::Active->value)->orderBy('label'),
                                ]);
                        },
                    ]);
            },
        ]);

        return new MobileRestaurantDetailResource($restaurant);
    }
}
