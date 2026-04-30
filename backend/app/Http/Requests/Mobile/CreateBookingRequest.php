<?php

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;
use Illuminate\Validation\ValidationException;
use App\Services\Bookings\BookingCreationValidator;

class CreateBookingRequest extends FormRequest
{
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
            $startsAt = $this->date('starts_at');
            if (! $startsAt instanceof Carbon) {
                $validator->errors()->add('starts_at', 'Invalid starts_at.');
                return;
            }

            try {
                app(BookingCreationValidator::class)->validate([
                    'restaurant_id' => (int) $this->input('restaurant_id'),
                    'branch_id' => (int) $this->input('branch_id'),
                    'seating_area_id' => $this->input('seating_area_id'),
                    'restaurant_table_id' => $this->input('restaurant_table_id'),
                    'starts_at' => $startsAt,
                    'party_size' => (int) $this->input('party_size'),
                ]);
            } catch (ValidationException $e) {
                foreach ($e->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($field, $message);
                    }
                }
            }
        });
    }
}

