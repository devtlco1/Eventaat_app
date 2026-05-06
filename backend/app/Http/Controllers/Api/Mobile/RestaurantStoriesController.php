<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\RestaurantStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\MobileStoryResource;
use App\Models\Restaurant;
use App\Models\RestaurantStory;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class RestaurantStoriesController extends Controller
{
    public function index(Restaurant $restaurant): AnonymousResourceCollection
    {
        if ($restaurant->status?->value !== RestaurantStatus::Active->value) {
            abort(404);
        }

        $now = Carbon::now();

        $stories = RestaurantStory::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('status', RestaurantStory::STATUS_PUBLISHED)
            ->where(function ($q) use ($now): void {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->orderBy('display_order')
            ->orderByDesc('created_at')
            ->with(['items' => function ($q): void {
                $q->orderBy('sort_order')->limit(1);
            }])
            ->get();

        return MobileStoryResource::collection($stories);
    }
}
