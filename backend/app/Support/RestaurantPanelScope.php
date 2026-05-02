<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantEvent;
use App\Models\RestaurantMenu;
use App\Models\RestaurantOffer;
use App\Models\RestaurantReview;
use App\Models\RestaurantStaffAssignment;
use App\Models\RestaurantStory;
use App\Models\RestaurantTable;
use App\Models\SeatingArea;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class RestaurantPanelScope
{
    public static function restaurants(User $user): Builder
    {
        return Restaurant::query()
            ->whereIn('id', $user->scopedRestaurantIds());
    }

    public static function branches(User $user): Builder
    {
        $branchIds = $user->scopedBranchIds();

        if (count($branchIds)) {
            return Branch::query()->whereIn('id', $branchIds);
        }

        return Branch::query()->whereIn('restaurant_id', $user->scopedRestaurantIds());
    }

    public static function seatingAreas(User $user): Builder
    {
        return SeatingArea::query()
            ->whereIn('branch_id', self::branches($user)->select('id'));
    }

    public static function tables(User $user): Builder
    {
        return RestaurantTable::query()
            ->whereIn('seating_area_id', self::seatingAreas($user)->select('id'));
    }

    public static function staffAssignments(User $user): Builder
    {
        $branchIds = $user->scopedBranchIds();

        $query = RestaurantStaffAssignment::query()
            ->whereIn('restaurant_id', $user->scopedRestaurantIds());

        if (count($branchIds)) {
            $query->where(function (Builder $q) use ($branchIds) {
                $q->whereNull('branch_id')->orWhereIn('branch_id', $branchIds);
            });
        }

        return $query;
    }

    public static function bookings(User $user): Builder
    {
        $branchIds = $user->scopedBranchIds();

        $query = Booking::query()
            ->whereIn('restaurant_id', $user->scopedRestaurantIds());

        if (count($branchIds)) {
            $query->whereIn('branch_id', $branchIds);
        }

        return $query;
    }

    public static function offers(User $user): Builder
    {
        $branchIds = $user->scopedBranchIds();

        if (count($branchIds)) {
            return RestaurantOffer::query()->whereIn('branch_id', $branchIds);
        }

        return RestaurantOffer::query()->whereIn('restaurant_id', $user->scopedRestaurantIds());
    }

    public static function stories(User $user): Builder
    {
        $branchIds = $user->scopedBranchIds();

        if (count($branchIds)) {
            return RestaurantStory::query()->whereIn('branch_id', $branchIds);
        }

        return RestaurantStory::query()->whereIn('restaurant_id', $user->scopedRestaurantIds());
    }

    public static function reviews(User $user): Builder
    {
        $branchIds = $user->scopedBranchIds();

        if (count($branchIds)) {
            return RestaurantReview::query()->whereIn('branch_id', $branchIds);
        }

        return RestaurantReview::query()->whereIn('restaurant_id', $user->scopedRestaurantIds());
    }

    public static function supportTickets(User $user): Builder
    {
        $branchIds = $user->scopedBranchIds();

        if (count($branchIds)) {
            return SupportTicket::query()->whereIn('branch_id', $branchIds);
        }

        return SupportTicket::query()->whereIn('restaurant_id', $user->scopedRestaurantIds());
    }

    public static function menus(User $user): Builder
    {
        $branchIds = $user->scopedBranchIds();

        if (count($branchIds)) {
            return RestaurantMenu::query()->whereIn('branch_id', $branchIds);
        }

        return RestaurantMenu::query()->whereIn('restaurant_id', $user->scopedRestaurantIds());
    }

    public static function restaurantEvents(User $user): Builder
    {
        $restaurantIds = $user->scopedRestaurantIds();
        $branchIds = $user->scopedBranchIds();

        $query = RestaurantEvent::query()->whereIn('restaurant_id', $restaurantIds);

        if (count($branchIds)) {
            $query->whereIn('branch_id', $branchIds);
        }

        return $query;
    }
}
