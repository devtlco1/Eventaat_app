<?php

namespace App\Filament\Restaurant\Resources\Bookings\Pages;

use App\Filament\Restaurant\Resources\Bookings\BookingResource;
use App\Filament\Restaurant\Resources\Bookings\Schemas\ManualBookingCreateForm;
use App\Models\User;
use App\Services\Bookings\ManualBookingCreationService;
use App\Support\ManualBookingFormSupport;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    protected static bool $canCreateAnother = false;

    public function form(Schema $schema): Schema
    {
        return ManualBookingCreateForm::configure($schema);
    }

    protected function handleRecordCreation(array $data): Model
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();
        $allowedRestaurantIds = $user?->scopedRestaurantIds() ?? [];
        $allowedBranchIds = $user?->scopedBranchIds() ?? [];

        $restaurantId = (int) ($data['restaurant_id'] ?? 0);
        if ($restaurantId === 0 && count($allowedRestaurantIds) === 1) {
            $restaurantId = (int) $allowedRestaurantIds[0];
        }

        $branchId = (int) ($data['branch_id'] ?? 0);
        if ($branchId === 0 && count($allowedBranchIds) === 1) {
            $branchId = (int) $allowedBranchIds[0];
        }

        $startsAt = ManualBookingFormSupport::parseStartsAtOrThrowForFilamentForm($data['starts_at'] ?? null);

        try {
            return app(ManualBookingCreationService::class)->create([
                'customer_phone' => (string) ($data['customer_phone'] ?? ''),
                'customer_name' => $data['customer_name'] ?? null,
                'restaurant_id' => $restaurantId,
                'branch_id' => $branchId,
                'seating_area_id' => ($data['seating_area_id'] ?? null) !== null ? (int) $data['seating_area_id'] : null,
                'restaurant_table_id' => ($data['restaurant_table_id'] ?? null) !== null ? (int) $data['restaurant_table_id'] : null,
                'starts_at' => $startsAt,
                'party_size' => (int) ($data['party_size'] ?? 0),
                'customer_note' => $data['customer_note'] ?? null,
                'restaurant_note' => $data['restaurant_note'] ?? null,
                'allowed_restaurant_ids' => $allowedRestaurantIds,
                'allowed_branch_ids' => $allowedBranchIds,
            ]);
        } catch (ValidationException $exception) {
            throw ManualBookingFormSupport::remapValidationExceptionForFilamentForm($exception);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResourceUrl();
    }
}
