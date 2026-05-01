<?php

namespace App\Filament\Platform\Resources\Bookings\Pages;

use App\Filament\Platform\Resources\Bookings\BookingResource;
use App\Filament\Platform\Resources\Bookings\Schemas\ManualBookingCreateForm;
use App\Services\Bookings\ManualBookingCreationService;
use App\Support\ManualBookingFormSupport;
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
        $startsAt = ManualBookingFormSupport::parseStartsAtOrThrowForFilamentForm($data['starts_at'] ?? null);

        try {
            return app(ManualBookingCreationService::class)->create([
                'customer_phone' => (string) ($data['customer_phone'] ?? ''),
                'customer_name' => $data['customer_name'] ?? null,
                'restaurant_id' => (int) ($data['restaurant_id'] ?? 0),
                'branch_id' => (int) ($data['branch_id'] ?? 0),
                'seating_area_id' => ($data['seating_area_id'] ?? null) !== null ? (int) $data['seating_area_id'] : null,
                'restaurant_table_id' => ($data['restaurant_table_id'] ?? null) !== null ? (int) $data['restaurant_table_id'] : null,
                'restaurant_event_id' => ($data['restaurant_event_id'] ?? null) !== null ? (int) $data['restaurant_event_id'] : null,
                'starts_at' => $startsAt,
                'party_size' => (int) ($data['party_size'] ?? 0),
                'customer_note' => $data['customer_note'] ?? null,
                'restaurant_note' => $data['restaurant_note'] ?? null,
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
