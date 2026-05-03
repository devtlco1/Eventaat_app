<?php

namespace App\Http\Requests\Mobile;

use App\Services\Otp\MobileOtpService;
use Illuminate\Foundation\Http\FormRequest;

class RequestOtpRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'phone' => MobileOtpService::otpPhoneValidationRules(),
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
