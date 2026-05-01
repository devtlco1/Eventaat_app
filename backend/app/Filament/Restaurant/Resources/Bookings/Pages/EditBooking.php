<?php

namespace App\Filament\Restaurant\Resources\Bookings\Pages;

use App\Filament\Restaurant\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Models\User;
use App\Services\Bookings\BookingCreationValidator;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;
use Filament\Resources\Pages\EditRecord;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Booking $record */
        $record = $this->getRecord();

        /** @var User|null $user */
        $user = Filament::auth()->user();
        $allowedRestaurantIds = $user?->scopedRestaurantIds() ?? [];
        $allowedBranchIds = $user?->scopedBranchIds() ?? [];

        try {
            app(BookingCreationValidator::class)->validate([
                'restaurant_id' => (int) $record->restaurant_id,
                'branch_id' => (int) $record->branch_id,
                'seating_area_id' => $record->seating_area_id,
                'restaurant_table_id' => $record->restaurant_table_id,
                'restaurant_event_id' => $data['restaurant_event_id'] ?? null,
                'starts_at' => $record->starts_at,
                'party_size' => (int) $record->party_size,
                'allowed_restaurant_ids' => $allowedRestaurantIds,
                'allowed_branch_ids' => $allowedBranchIds,
            ]);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages([
                'data.restaurant_event_id' => $e->errors()['restaurant_event_id'] ?? ['Invalid event selection.'],
            ]);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
