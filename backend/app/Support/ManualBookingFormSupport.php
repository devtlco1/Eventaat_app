<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

final class ManualBookingFormSupport
{
    /**
     * Filament manual booking CreateRecord stores fields under Livewire state path `data.*`.
     * ManualBookingCreationService throws Laravel ValidationException keys without that prefix,
     * which prevents errors from attaching to visible fields.
     */
    public static function remapValidationExceptionForFilamentForm(ValidationException $exception): ValidationException
    {
        $messages = [];
        foreach ($exception->errors() as $field => $errs) {
            $key = str_starts_with($field, 'data.') ? $field : 'data.'.$field;
            $messages[$key] = $errs;
        }

        return ValidationException::withMessages($messages);
    }

    public static function parseStartsAtOrThrowForFilamentForm(mixed $raw): Carbon
    {
        try {
            if ($raw instanceof Carbon) {
                return $raw->copy();
            }

            return Carbon::parse((string) $raw);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'data.starts_at' => ['Invalid starts at date/time.'],
            ]);
        }
    }
}
