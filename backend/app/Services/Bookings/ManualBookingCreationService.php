<?php

namespace App\Services\Bookings;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Services\Otp\MobileOtpService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManualBookingCreationService
{
    public function __construct(
        private readonly BookingCreationValidator $validator,
    ) {}

    /**
     * @param  array{
     *   customer_phone:string,
     *   customer_name?:string|null,
     *   restaurant_id:int,
     *   branch_id:int,
     *   seating_area_id?:int|null,
     *   restaurant_table_id?:int|null,
     *   starts_at:\Illuminate\Support\Carbon,
     *   party_size:int,
     *   customer_note?:string|null,
     *   restaurant_note?:string|null,
     *   allowed_restaurant_ids?:array<int,int>,
     *   allowed_branch_ids?:array<int,int>,
     * }  $data
     */
    public function create(array $data): Booking
    {
        $phone = MobileOtpService::normalizePhone((string) ($data['customer_phone'] ?? ''));
        if ($phone === '') {
            throw ValidationException::withMessages([
                'customer_phone' => ['Customer phone is required.'],
            ]);
        }

        $customerName = array_key_exists('customer_name', $data) ? $data['customer_name'] : null;
        $customerName = is_string($customerName) ? trim($customerName) : null;
        $customerName = $customerName !== '' ? $customerName : null;

        /** @var User|null $existing */
        $existing = User::query()->where('phone', $phone)->first();
        if (! $existing && $customerName === null) {
            throw ValidationException::withMessages([
                'customer_name' => ['Customer name is required for a new customer.'],
            ]);
        }

        $customer = $this->resolveOrCreateCustomer($phone, $customerName);

        $result = $this->validator->validate([
            'restaurant_id' => (int) $data['restaurant_id'],
            'branch_id' => (int) $data['branch_id'],
            'seating_area_id' => array_key_exists('seating_area_id', $data) ? $data['seating_area_id'] : null,
            'restaurant_table_id' => array_key_exists('restaurant_table_id', $data) ? $data['restaurant_table_id'] : null,
            'starts_at' => $data['starts_at'],
            'party_size' => (int) $data['party_size'],
            'allowed_restaurant_ids' => $data['allowed_restaurant_ids'] ?? null,
            'allowed_branch_ids' => $data['allowed_branch_ids'] ?? null,
        ]);

        $startsAt = $data['starts_at'];
        if (! $startsAt instanceof Carbon) {
            throw ValidationException::withMessages([
                'starts_at' => ['Invalid starts_at.'],
            ]);
        }

        $booking = Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => (int) $data['restaurant_id'],
            'branch_id' => (int) $data['branch_id'],
            'seating_area_id' => $result['seating_area_id'],
            'restaurant_table_id' => array_key_exists('restaurant_table_id', $data) ? ($data['restaurant_table_id'] !== null ? (int) $data['restaurant_table_id'] : null) : null,
            'starts_at' => $startsAt,
            'party_size' => (int) $data['party_size'],
            'status' => BookingStatus::Pending,
            'customer_note' => $data['customer_note'] ?? null,
            'restaurant_note' => $data['restaurant_note'] ?? null,
        ]);

        return $booking;
    }

    private function resolveOrCreateCustomer(string $phone, ?string $customerName): User
    {
        /** @var User|null $user */
        $user = User::query()->where('phone', $phone)->first();

        if ($user) {
            if (! $user->hasRole('customer')) {
                $user->assignRole('customer');
            }

            if ($customerName !== null) {
                $current = trim((string) $user->name);
                if ($current === '' || Str::lower($current) === 'customer') {
                    $user->forceFill(['name' => $customerName])->save();
                }
            }

            return $user;
        }

        $email = MobileOtpService::mobileEmailForPhone($phone);

        $user = User::create([
            'name' => $customerName ?? 'Customer',
            'phone' => $phone,
            'email' => $email,
            // password not used for mobile OTP login, but required for user table.
            'password' => Hash::make(Str::random(32)),
        ]);

        $user->syncRoles(['customer']);

        return $user;
    }
}

