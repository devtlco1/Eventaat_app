<?php

namespace App\Http\Requests\Mobile;

use App\Services\Otp\MobileOtpService;
use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'min:6', 'max:32'],
            'otp' => ['required', 'string', 'min:4', 'max:8'],
            'name' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $this->merge([
                'phone' => MobileOtpService::normalizePhone((string) $this->input('phone')),
            ]);
        }
    }
}

