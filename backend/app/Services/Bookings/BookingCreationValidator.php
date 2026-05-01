<?php

namespace App\Services\Bookings;

use App\Enums\BookingStatus;
use App\Enums\BranchStatus;
use App\Enums\RestaurantStatus;
use App\Enums\TableStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantEvent;
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
     *   restaurant_event_id?:int|null,
     *   starts_at:Carbon,
     *   party_size:int,
     *   allowed_restaurant_ids?:array<int,int>,
     *   allowed_branch_ids?:array<int,int>,
     * }  $data
     * @return array{seating_area_id:int|null}
     *
     * @throws ValidationException
     */
    public function validate(array $data): array
    {
        $restaurantId = (int) $data['restaurant_id'];
        $branchId = (int) $data['branch_id'];
        $partySize = (int) $data['party_size'];

        $seatingAreaId = array_key_exists('seating_area_id', $data) ? $data['seating_area_id'] : null;
        $tableId = array_key_exists('restaurant_table_id', $data) ? $data['restaurant_table_id'] : null;
        $eventId = array_key_exists('restaurant_event_id', $data) ? $data['restaurant_event_id'] : null;

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

        if ($eventId !== null) {
            $this->validateRestaurantEventForBooking(
                restaurant: $restaurant,
                branch: $branch,
                startsAt: $data['starts_at'],
                partySize: $partySize,
                eventId: (int) $eventId,
                allowedBranchIds: $data['allowed_branch_ids'] ?? null,
            );
        }

        $startsAt = $data['starts_at'];
        if (! $startsAt instanceof Carbon) {
            throw ValidationException::withMessages([
                'starts_at' => ['Invalid starts_at.'],
            ]);
        }

        $branch->loadMissing('availabilityRule');
        $this->validateBranchAvailability($branch, $startsAt);

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

    /**
     * @param  array<int,int>|null  $allowedBranchIds
     *
     * @throws ValidationException
     */
    private function validateRestaurantEventForBooking(
        Restaurant $restaurant,
        Branch $branch,
        Carbon $startsAt,
        int $partySize,
        int $eventId,
        ?array $allowedBranchIds,
    ): void {
        /** @var RestaurantEvent|null $event */
        $event = RestaurantEvent::query()->find($eventId);
        if (! $event || $event->restaurant_id !== $restaurant->id) {
            throw ValidationException::withMessages([
                'restaurant_event_id' => ['Event must belong to the selected restaurant.'],
            ]);
        }

        if ($event->status !== RestaurantEvent::STATUS_PUBLISHED) {
            throw ValidationException::withMessages([
                'restaurant_event_id' => ['Event must be published.'],
            ]);
        }

        if (! in_array($event->booking_mode, [RestaurantEvent::BOOKING_MODE_EVENT, RestaurantEvent::BOOKING_MODE_NORMAL], true)) {
            throw ValidationException::withMessages([
                'restaurant_event_id' => ['This event cannot accept bookings.'],
            ]);
        }

        if ($event->branch_id !== null && (int) $event->branch_id !== (int) $branch->id) {
            throw ValidationException::withMessages([
                'restaurant_event_id' => ['Event branch must match the selected booking branch.'],
            ]);
        }

        // Branch-scoped staff must only link branch-scoped events (restaurant-wide events are owner-only in restaurant panel).
        if ($allowedBranchIds !== null && $allowedBranchIds !== []) {
            if ($event->branch_id === null || ! in_array((int) $event->branch_id, $allowedBranchIds, true)) {
                throw ValidationException::withMessages([
                    'restaurant_event_id' => ['Event is out of scope.'],
                ]);
            }
        }

        if ($event->capacity !== null) {
            $consumingStatuses = [
                BookingStatus::Pending->value,
                BookingStatus::Accepted->value,
                BookingStatus::Arrived->value,
                BookingStatus::Seated->value,
            ];

            $current = (int) Booking::query()
                ->where('restaurant_event_id', $event->id)
                ->whereIn('status', $consumingStatuses)
                ->sum('party_size');

            if ($current + $partySize > (int) $event->capacity) {
                throw ValidationException::withMessages([
                    'restaurant_event_id' => ['Event capacity has been reached.'],
                ]);
            }
        }
    }

    private function validateBranchAvailability(Branch $branch, Carbon $startsAt): void
    {
        $rule = $branch->availabilityRule;
        if (! $rule) {
            return;
        }

        if (! $rule->is_booking_enabled) {
            throw ValidationException::withMessages([
                'branch_id' => ['Booking is disabled for this branch.'],
            ]);
        }

        $now = Carbon::now();

        $minAllowed = $now->copy()->addMinutes((int) $rule->min_advance_minutes);
        if ($startsAt->lessThan($minAllowed)) {
            throw ValidationException::withMessages([
                'starts_at' => ["Bookings must be made at least {$rule->min_advance_minutes} minutes in advance."],
            ]);
        }

        $latestAllowed = $now->copy()->addDays((int) $rule->max_advance_days)->endOfDay();
        if ($startsAt->greaterThan($latestAllowed)) {
            throw ValidationException::withMessages([
                'starts_at' => ["Bookings can only be made up to {$rule->max_advance_days} days in advance."],
            ]);
        }

        $weekdayField = match ($startsAt->dayOfWeekIso) {
            1 => 'mon',
            2 => 'tue',
            3 => 'wed',
            4 => 'thu',
            5 => 'fri',
            6 => 'sat',
            7 => 'sun',
            default => null,
        };

        if ($weekdayField === null || ! (bool) $rule->{$weekdayField}) {
            throw ValidationException::withMessages([
                'starts_at' => ['Bookings are not available on this weekday.'],
            ]);
        }

        $bookingTime = $startsAt->format('H:i:s');
        $open = $this->normalizeTime($rule->open_time);
        $close = $this->normalizeTime($rule->close_time);

        if ($open !== null && $bookingTime < $open) {
            throw ValidationException::withMessages([
                'starts_at' => ['Booking start time is before this branch opens.'],
            ]);
        }

        if ($close !== null && $bookingTime > $close) {
            throw ValidationException::withMessages([
                'starts_at' => ['Booking start time is after this branch closes.'],
            ]);
        }
    }

    private function normalizeTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->format('H:i:s');
        }

        return (string) $value;
    }
}
