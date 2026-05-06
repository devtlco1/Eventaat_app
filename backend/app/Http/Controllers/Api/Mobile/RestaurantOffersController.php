<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\RestaurantStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\MobileOfferResource;
use App\Models\Restaurant;
use App\Models\RestaurantOffer;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class RestaurantOffersController extends Controller
{
    public function index(Restaurant $restaurant): AnonymousResourceCollection
    {
        if ($restaurant->status?->value !== RestaurantStatus::Active->value) {
            abort(404);
        }

        $now = Carbon::now();

        $offers = RestaurantOffer::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('status', RestaurantOffer::STATUS_PUBLISHED)
            ->where(function ($q) use ($now): void {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->orderByDesc('created_at')
            ->get();

        return MobileOfferResource::collection($offers);
    }
}
