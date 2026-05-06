<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\RestaurantStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\MobileEventResource;
use App\Models\Restaurant;
use App\Models\RestaurantEvent;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class RestaurantEventsController extends Controller
{
    public function index(Restaurant $restaurant): AnonymousResourceCollection
    {
        if ($restaurant->status?->value !== RestaurantStatus::Active->value) {
            abort(404);
        }

        $now = Carbon::now();

        $events = RestaurantEvent::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('status', RestaurantEvent::STATUS_PUBLISHED)
            ->where('starts_at', '>=', $now)
            ->orderBy('starts_at')
            ->with(['restaurant', 'branch'])
            ->get();

        return MobileEventResource::collection($events);
    }
}
