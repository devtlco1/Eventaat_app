<?php

namespace App\Services\Bookings;

use App\Enums\BranchStatus;
use App\Enums\BookingStatus;
use App\Enums\RestaurantStatus;
use App\Enums\TableStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\SeatingArea;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class BookingCreationValidator
{
    public const DEFAULT_RESERVATION_MINUTES = 120;

    /**
     * @param  array{
     *   restaurant_id:int,
     *   branch_id:int,
     *   seating_area_id?:int|null,
     *   restaurant_table_id?:int|null,
     *   starts_at:\Illuminate\Support\Carbon,
     *   party_size:int,
     *   allowed_restaurant_ids?:array<int,int>,
     *   allowed_branch_ids?:array<int,int>,
     * }  $data
     * @return array{seating_area_id:int|null}
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate(array $data): array
    {
        $restaurantId = (int) $data['restaurant_id'];
        $branchId = (int) $data['branch_id'];
        $partySize = (int) $data['party_size'];

        $seatingAreaId = array_key_exists('seating_area_id', $data) ? $data['seating_area_id'] : null;
        $tableId = array_key_exists('restaurant_table_id', $data) ? $data['restaurant_table_id'] : null;

        if (isset($data['allowed_restaurant_ids']) && $data['allowed_restaurant_ids'] !== [] && ! in_array($restaurantId, $data['allowed_restaurant_ids'], true)) {
            throw ValidationException::withMessages([
                'restaurant_id' => ['Restaurant is out of scope.'],
            ]);
        }

        if (isset($data['allowed_branch_ids']) && $data['allowed_branch_ids'] !== [] && ! in_array($branchId, $data['allowed_branch_ids'], true)) {
            throw ValidationException::withMessages([
                'branch_id' => ['Branch is out of scope.'],
            ]);
        }

        /** @var Restaurant|null $restaurant */
        $restaurant = Restaurant::query()->find($restaurantId);
        if (! $restaurant || $restaurant->status?->value !== RestaurantStatus::Active->value) {
            throw ValidationException::withMessages([
                'restaurant_id' => ['Restaurant must be active.'],
            ]);
        }

        /** @var Branch|null $branch */
        $branch = Branch::query()->find($branchId);
        if (! $branch || $branch->status?->value !== BranchStatus::Active->value) {
            throw ValidationException::withMessages([
                'branch_id' => ['Branch must be active.'],
            ]);
        }

        if ($branch->restaurant_id !== $restaurant->id) {
            throw ValidationException::withMessages([
                'branch_id' => ['Branch must belong to the selected restaurant.'],
            ]);
        }

        $seatingArea = null;
        if ($seatingAreaId !== null) {
            /** @var SeatingArea|null $seatingArea */
            $seatingArea = SeatingArea::query()->find((int) $seatingAreaId);

            if (! $seatingArea || $seatingArea->branch_id !== $branch->id || $seatingArea->status !== 'active') {
                throw ValidationException::withMessages([
                    'seating_area_id' => ['Seating area must belong to the selected branch and be active.'],
                ]);
            }
        }

        // If a table is provided but seating area is not, infer seating area (mirrors API controller behavior).
        if ($seatingAreaId === null && $tableId !== null) {
            /** @var RestaurantTable|null $table */
            $table = RestaurantTable::query()->find((int) $tableId);
            $seatingAreaId = $table?->seating_area_id;
        }

        if ($tableId !== null) {
            /** @var RestaurantTable|null $table */
            $table = RestaurantTable::query()
                ->with('seatingArea')
                ->find((int) $tableId);

            if (! $table || $table->status?->value !== TableStatus::Active->value) {
                throw ValidationException::withMessages([
                    'restaurant_table_id' => ['Table must be active.'],
                ]);
            }

            if ($partySize > (int) $table->capacity) {
                throw ValidationException::withMessages([
                    'party_size' => ['Party size must not exceed table capacity.'],
                ]);
            }

            $tableArea = $table->seatingArea;
            if (! $tableArea || $tableArea->branch_id !== $branch->id || $tableArea->status !== 'active') {
                throw ValidationException::withMessages([
                    'restaurant_table_id' => ['Table must belong to an active seating area in the selected branch.'],
                ]);
            }

            if ($seatingArea && $tableArea->id !== $seatingArea->id) {
                throw ValidationException::withMessages([
                    'restaurant_table_id' => ['Table must belong to the selected seating area.'],
                ]);
            }

            $startsAt = $data['starts_at'];
            if (! $startsAt instanceof Carbon) {
                throw ValidationException::withMessages([
                    'starts_at' => ['Invalid starts_at.'],
                ]);
            }

            $bufferStart = (clone $startsAt)->subMinutes(self::DEFAULT_RESERVATION_MINUTES);
            $bufferEnd = (clone $startsAt)->addMinutes(self::DEFAULT_RESERVATION_MINUTES);

            $conflictExists = Booking::query()
                ->where('restaurant_table_id', $table->id)
                ->whereIn('status', [BookingStatus::Pending->value, BookingStatus::Accepted->value])
                ->whereBetween('starts_at', [$bufferStart, $bufferEnd])
                ->exists();

            if ($conflictExists) {
                throw ValidationException::withMessages([
                    'restaurant_table_id' => ['Table is not available at the selected time.'],
                ]);
            }
        }

        return [
            'seating_area_id' => $seatingAreaId !== null ? (int) $seatingAreaId : null,
        ];
    }
}

