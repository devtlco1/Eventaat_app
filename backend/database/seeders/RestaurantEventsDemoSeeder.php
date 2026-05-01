<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class RestaurantEventsDemoSeeder extends Seeder
{
    /**
     * Idempotent Phase 11A demo data.
     */
    public function run(): void
    {
        $restaurantA = Restaurant::where('slug', 'demo-restaurant-a')->first();
        $restaurantB = Restaurant::where('slug', 'demo-restaurant-b')->first();

        if (! $restaurantA || ! $restaurantB) {
            return;
        }

        $branchA1 = Branch::where('restaurant_id', $restaurantA->id)->where('code', 'main')->first();
        $branchB1 = Branch::where('restaurant_id', $restaurantB->id)->where('code', 'main')->first();

        $startsA = Carbon::now()->addDays(7)->setTime(20, 0);
        $startsB = Carbon::now()->addDays(10)->setTime(19, 30);

        RestaurantEvent::updateOrCreate(
            ['slug' => 'demo-a-live-music-night'],
            [
                'restaurant_id' => $restaurantA->id,
                'branch_id' => $branchA1?->id,
                'title' => 'Live Music Night',
                'description' => 'A special evening with live music.',
                'starts_at' => $startsA,
                'ends_at' => (clone $startsA)->addHours(3),
                'status' => RestaurantEvent::STATUS_PUBLISHED,
                'booking_mode' => RestaurantEvent::BOOKING_MODE_INFO_ONLY,
                'capacity' => null,
                'price_label' => null,
                'notes' => 'Seeded demo event (Phase 11A).',
            ],
        );

        // Restaurant-wide (owner-only in restaurant panel).
        RestaurantEvent::updateOrCreate(
            ['slug' => 'demo-b-chefs-tasting-night'],
            [
                'restaurant_id' => $restaurantB->id,
                'branch_id' => null,
                'title' => "Chef's Tasting Night",
                'description' => 'A curated tasting experience by the chef.',
                'starts_at' => $startsB,
                'ends_at' => (clone $startsB)->addHours(2),
                'status' => RestaurantEvent::STATUS_DRAFT,
                'booking_mode' => RestaurantEvent::BOOKING_MODE_INFO_ONLY,
                'capacity' => 30,
                'price_label' => 'Fixed menu',
                'notes' => 'Seeded demo event (Phase 11A).',
            ],
        );
    }
}

