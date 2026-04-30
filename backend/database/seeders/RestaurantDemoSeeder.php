<?php

namespace Database\Seeders;

use App\Enums\BranchStatus;
use App\Enums\RestaurantStaffRole;
use App\Enums\RestaurantStatus;
use App\Enums\SeatingAreaType;
use App\Enums\TableStatus;
use App\Models\Branch;
use App\Models\BranchAvailabilityRule;
use App\Models\Restaurant;
use App\Models\RestaurantStaffAssignment;
use App\Models\RestaurantTable;
use App\Models\SeatingArea;
use App\Models\User;
use Illuminate\Database\Seeder;

class RestaurantDemoSeeder extends Seeder
{
    /**
     * Idempotent Phase 2 demo data.
     */
    public function run(): void
    {
        $restaurantA = Restaurant::updateOrCreate(
            ['slug' => 'demo-restaurant-a'],
            ['name' => 'Demo Restaurant A', 'status' => RestaurantStatus::Active],
        );

        $restaurantB = Restaurant::updateOrCreate(
            ['slug' => 'demo-restaurant-b'],
            ['name' => 'Demo Restaurant B', 'status' => RestaurantStatus::Active],
        );

        $branchA1 = Branch::updateOrCreate(
            ['restaurant_id' => $restaurantA->id, 'code' => 'main'],
            ['name' => 'Main Branch', 'status' => BranchStatus::Active],
        );

        $branchB1 = Branch::updateOrCreate(
            ['restaurant_id' => $restaurantB->id, 'code' => 'main'],
            ['name' => 'Main Branch', 'status' => BranchStatus::Active],
        );

        foreach ([$branchA1, $branchB1] as $branch) {
            BranchAvailabilityRule::updateOrCreate(
                ['branch_id' => $branch->id],
                [
                    'is_booking_enabled' => true,
                    'booking_duration_minutes' => 90,
                    'min_advance_minutes' => 60,
                    'max_advance_days' => 30,
                    'open_time' => '10:00:00',
                    'close_time' => '23:00:00',
                    'mon' => true,
                    'tue' => true,
                    'wed' => true,
                    'thu' => true,
                    'fri' => true,
                    'sat' => true,
                    'sun' => true,
                    'notes' => null,
                ]
            );
        }

        $areaAIndoor = SeatingArea::updateOrCreate(
            ['branch_id' => $branchA1->id, 'code' => 'indoor'],
            ['name' => 'Indoor', 'type' => SeatingAreaType::Indoor, 'status' => 'active'],
        );

        $areaBIndoor = SeatingArea::updateOrCreate(
            ['branch_id' => $branchB1->id, 'code' => 'indoor'],
            ['name' => 'Indoor', 'type' => SeatingAreaType::Indoor, 'status' => 'active'],
        );

        foreach (['T1' => 2, 'T2' => 4] as $label => $capacity) {
            RestaurantTable::updateOrCreate(
                ['seating_area_id' => $areaAIndoor->id, 'label' => $label],
                ['capacity' => $capacity, 'status' => TableStatus::Active],
            );
        }

        foreach (['T1' => 2, 'T2' => 4] as $label => $capacity) {
            RestaurantTable::updateOrCreate(
                ['seating_area_id' => $areaBIndoor->id, 'label' => $label],
                ['capacity' => $capacity, 'status' => TableStatus::Active],
            );
        }

        $owner = User::where('email', 'restaurant_owner@eventaat.test')->first();
        $manager = User::where('email', 'branch_manager@eventaat.test')->first();
        $host = User::where('email', 'restaurant_host@eventaat.test')->first();

        if ($owner) {
            RestaurantStaffAssignment::updateOrCreate(
                [
                    'user_id' => $owner->id,
                    'restaurant_id' => $restaurantA->id,
                    'branch_id' => null,
                    'role' => RestaurantStaffRole::RestaurantOwner,
                ],
                ['status' => 'active'],
            );
        }

        if ($manager) {
            RestaurantStaffAssignment::updateOrCreate(
                [
                    'user_id' => $manager->id,
                    'restaurant_id' => $restaurantA->id,
                    'branch_id' => $branchA1->id,
                    'role' => RestaurantStaffRole::BranchManager,
                ],
                ['status' => 'active'],
            );
        }

        if ($host) {
            RestaurantStaffAssignment::updateOrCreate(
                [
                    'user_id' => $host->id,
                    'restaurant_id' => $restaurantA->id,
                    'branch_id' => $branchA1->id,
                    'role' => RestaurantStaffRole::RestaurantHost,
                ],
                ['status' => 'active'],
            );
        }
    }
}
