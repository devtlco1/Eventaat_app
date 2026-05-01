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
use App\Models\RestaurantOffer;
use App\Models\RestaurantStory;
use App\Models\SeatingArea;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

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

        $start = Carbon::now()->subDays(1)->startOfHour();
        $end = Carbon::now()->addDays(14)->startOfHour();

        RestaurantOffer::updateOrCreate(
            ['slug' => 'demo-lunch-combo-offer'],
            [
                'restaurant_id' => $restaurantA->id,
                'branch_id' => null,
                'title' => 'Lunch Combo Offer',
                'description' => 'Weekday lunch combo — limited time.',
                'status' => RestaurantOffer::STATUS_PUBLISHED,
                'offer_type' => RestaurantOffer::TYPE_FIXED_AMOUNT,
                'discount_value' => 15.00,
                'starts_at' => $start,
                'ends_at' => $end,
                'terms' => 'Valid on weekdays only. Not combinable with other offers.',
                'notes' => null,
            ],
        );

        RestaurantOffer::updateOrCreate(
            ['slug' => 'demo-chef-special-discount'],
            [
                'restaurant_id' => $restaurantB->id,
                'branch_id' => $branchB1->id,
                'title' => 'Chef Special Discount',
                'description' => 'Seasonal chef special — while supplies last.',
                'status' => RestaurantOffer::STATUS_PUBLISHED,
                'offer_type' => RestaurantOffer::TYPE_PERCENTAGE,
                'discount_value' => 10.00,
                'starts_at' => $start,
                'ends_at' => $end,
                'terms' => 'Valid for dine-in only.',
                'notes' => null,
            ],
        );

        RestaurantStory::updateOrCreate(
            ['slug' => 'demo-story-a-image'],
            [
                'restaurant_id' => $restaurantA->id,
                'branch_id' => null,
                'title' => 'Welcome story',
                'story_type' => RestaurantStory::TYPE_IMAGE,
                'media_url' => 'https://example.com/demo-story-a.jpg',
                'body' => null,
                'cta_label' => 'Book now',
                'cta_url' => 'https://example.com',
                'status' => RestaurantStory::STATUS_PUBLISHED,
                'starts_at' => $start,
                'ends_at' => $end,
                'display_order' => 0,
                'notes' => null,
            ],
        );

        RestaurantStory::updateOrCreate(
            ['slug' => 'demo-story-b-text'],
            [
                'restaurant_id' => $restaurantB->id,
                'branch_id' => $branchB1->id,
                'title' => 'Chef note',
                'story_type' => RestaurantStory::TYPE_TEXT,
                'media_url' => null,
                'body' => 'Today’s special is available while supplies last.',
                'cta_label' => null,
                'cta_url' => null,
                'status' => RestaurantStory::STATUS_PUBLISHED,
                'starts_at' => $start,
                'ends_at' => $end,
                'display_order' => 0,
                'notes' => null,
            ],
        );
    }
}
