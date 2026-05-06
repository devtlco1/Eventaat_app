<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\RestaurantStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\MobileMenuResource;
use App\Models\Restaurant;
use App\Models\RestaurantMenu;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RestaurantMenusController extends Controller
{
    public function index(Restaurant $restaurant): AnonymousResourceCollection
    {
        if ($restaurant->status?->value !== RestaurantStatus::Active->value) {
            abort(404);
        }

        $menus = $restaurant->menus()
            ->where('status', RestaurantMenu::STATUS_PUBLISHED)
            ->orderBy('display_order')
            ->orderBy('title')
            ->with([
                'categories' => function ($q): void {
                    $q->where('is_active', true)
                        ->orderBy('display_order')
                        ->with([
                            'items' => function ($q): void {
                                $q->where('is_available', true)
                                    ->orderBy('display_order');
                            },
                        ]);
                },
            ])
            ->get();

        return MobileMenuResource::collection($menus);
    }
}
