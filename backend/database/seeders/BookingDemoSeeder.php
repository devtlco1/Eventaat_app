<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\SeatingArea;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class BookingDemoSeeder extends Seeder
{
    /**
     * Idempotent Phase 5A demo data (minimal).
     */
    public function run(): void
    {
        $customer = User::where('email', 'customer@eventaat.test')->first();
        $restaurant = Restaurant::where('slug', 'demo-restaurant-a')->first();

        if (! $customer || ! $restaurant) {
            return;
        }

        $branch = Branch::where('restaurant_id', $restaurant->id)->where('code', 'main')->first();
        if (! $branch) {
            return;
        }

        $area = SeatingArea::where('branch_id', $branch->id)->where('code', 'indoor')->first();
        $table = $area
            ? RestaurantTable::where('seating_area_id', $area->id)->where('label', 'T2')->first()
            : null;

        $startsAt = Carbon::now()->addDay()->setTime(19, 0, 0);

        Booking::updateOrCreate(
            [
                'customer_id' => $customer->id,
                'branch_id' => $branch->id,
                'starts_at' => $startsAt,
            ],
            [
                'restaurant_id' => $restaurant->id,
                'seating_area_id' => $area?->id,
                'restaurant_table_id' => $table?->id,
                'party_size' => 2,
                'status' => BookingStatus::Pending,
                'customer_note' => 'Demo booking request',
            ]
        );
    }
}

