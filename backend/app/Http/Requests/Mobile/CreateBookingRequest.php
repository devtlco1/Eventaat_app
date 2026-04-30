<?php

namespace App\Http\Requests\Mobile;

use App\Enums\BranchStatus;
use App\Enums\BookingStatus;
use App\Enums\RestaurantStatus;
use App\Enums\TableStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\SeatingArea;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class CreateBookingRequest extends FormRequest
{
    private const DEFAULT_RESERVATION_MINUTES = 120;

    public function rules(): array
    {
        return [
            'restaurant_id' => ['required', 'integer'],
            'branch_id' => ['required', 'integer'],
            'seating_area_id' => ['nullable', 'integer'],
            'restaurant_table_id' => ['nullable', 'integer'],
            'starts_at' => ['required', 'date', 'after:now'],
            'party_size' => ['required', 'integer', 'min:1', 'max:100'],
            'customer_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $restaurantId = (int) $this->input('restaurant_id');
            $branchId = (int) $this->input('branch_id');
            $partySize = (int) $this->input('party_size');

            $restaurant = Restaurant::query()->find($restaurantId);
            if (! $restaurant || $restaurant->status?->value !== RestaurantStatus::Active->value) {
                $validator->errors()->add('restaurant_id', 'Restaurant must be active.');
                return;
            }

            $branch = Branch::query()->find($branchId);
            if (! $branch || $branch->status?->value !== BranchStatus::Active->value) {
                $validator->errors()->add('branch_id', 'Branch must be active.');
                return;
            }

            if ($branch->restaurant_id !== $restaurant->id) {
                $validator->errors()->add('branch_id', 'Branch must belong to the selected restaurant.');
                return;
            }

            $seatingAreaId = $this->input('seating_area_id');
            $seatingArea = null;
            if ($seatingAreaId !== null) {
                $seatingArea = SeatingArea::query()->find((int) $seatingAreaId);

                if (! $seatingArea || $seatingArea->branch_id !== $branch->id || $seatingArea->status !== 'active') {
                    $validator->errors()->add('seating_area_id', 'Seating area must belong to the selected branch and be active.');
                    return;
                }
            }

            $tableId = $this->input('restaurant_table_id');
            if ($tableId !== null) {
                /** @var RestaurantTable|null $table */
                $table = RestaurantTable::query()
                    ->with('seatingArea')
                    ->find((int) $tableId);

                if (! $table || $table->status?->value !== TableStatus::Active->value) {
                    $validator->errors()->add('restaurant_table_id', 'Table must be active.');
                    return;
                }

                if ($partySize > (int) $table->capacity) {
                    $validator->errors()->add('party_size', 'Party size must not exceed table capacity.');
                    return;
                }

                $tableArea = $table->seatingArea;
                if (! $tableArea || $tableArea->branch_id !== $branch->id || $tableArea->status !== 'active') {
                    $validator->errors()->add('restaurant_table_id', 'Table must belong to an active seating area in the selected branch.');
                    return;
                }

                if ($seatingArea && $tableArea->id !== $seatingArea->id) {
                    $validator->errors()->add('restaurant_table_id', 'Table must belong to the selected seating area.');
                    return;
                }

                $startsAt = $this->date('starts_at');
                if (! $startsAt instanceof Carbon) {
                    $validator->errors()->add('starts_at', 'Invalid starts_at.');
                    return;
                }

                // Conflict prevention (simple): treat bookings as fixed-duration blocks.
                $bufferStart = (clone $startsAt)->subMinutes(self::DEFAULT_RESERVATION_MINUTES);
                $bufferEnd = (clone $startsAt)->addMinutes(self::DEFAULT_RESERVATION_MINUTES);

                $conflictExists = Booking::query()
                    ->where('restaurant_table_id', $table->id)
                    ->whereIn('status', [BookingStatus::Pending->value, BookingStatus::Accepted->value])
                    ->whereBetween('starts_at', [$bufferStart, $bufferEnd])
                    ->exists();

                if ($conflictExists) {
                    $validator->errors()->add('restaurant_table_id', 'Table is not available at the selected time.');
                    return;
                }
            }
        });
    }
}

