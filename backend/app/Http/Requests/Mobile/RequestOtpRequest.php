<?php

namespace App\Http\Requests\Mobile;

use App\Services\Otp\MobileOtpService;
use Illuminate\Foundation\Http\FormRequest;

class RequestOtpRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'min:6', 'max:32'],
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

