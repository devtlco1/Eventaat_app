<?php

namespace App\Filament\Restaurant\Resources\Bookings\Pages;

use App\Filament\Restaurant\Resources\Bookings\BookingResource;
use App\Filament\Restaurant\Resources\Bookings\Schemas\ManualBookingCreateForm;
use App\Services\Bookings\ManualBookingCreationService;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    public function form(Schema $schema): Schema
    {
        return ManualBookingCreateForm::configure($schema);
    }

    protected function handleRecordCreation(array $data): Model
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();
        $allowedRestaurantIds = $user?->scopedRestaurantIds() ?? [];
        $allowedBranchIds = $user?->scopedBranchIds() ?? [];

        $startsAt = Carbon::parse((string) ($data['starts_at'] ?? ''));

        return app(ManualBookingCreationService::class)->create([
            'customer_phone' => (string) ($data['customer_phone'] ?? ''),
            'customer_name' => $data['customer_name'] ?? null,
            'restaurant_id' => (int) ($data['restaurant_id'] ?? 0),
            'branch_id' => (int) ($data['branch_id'] ?? 0),
            'seating_area_id' => ($data['seating_area_id'] ?? null) !== null ? (int) $data['seating_area_id'] : null,
            'restaurant_table_id' => ($data['restaurant_table_id'] ?? null) !== null ? (int) $data['restaurant_table_id'] : null,
            'starts_at' => $startsAt,
            'party_size' => (int) ($data['party_size'] ?? 0),
            'customer_note' => $data['customer_note'] ?? null,
            'restaurant_note' => $data['restaurant_note'] ?? null,
            'allowed_restaurant_ids' => $allowedRestaurantIds,
            'allowed_branch_ids' => $allowedBranchIds,
        ]);
    }
}
