<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Booking;
use App\Models\Restaurant;
use App\Models\RestaurantStaffAssignment;
use App\Models\RestaurantTable;
use App\Models\SeatingArea;
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
}

