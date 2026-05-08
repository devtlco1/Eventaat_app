<?php

namespace App\Services\Otp;

use Illuminate\Support\Facades\Log;

class LocalLogOtpSender implements OtpSender
{
    public function send(string $phone, string $otp): void
    {
        $context = ['phone_masked' => TwilioSmsOtpSender::maskPhone($phone)];

        // Expose the OTP code in the log only for local/testing environments so
        // developers can complete the auth flow without a real SMS provider.
        // In production the OTP is never written to any log or API response.
        if (app()->environment(['local', 'testing'])) {
            $context['otp'] = $otp;
        }

        Log::info('Mobile OTP (local/dev)', $context);

        OtpDeliveryAttemptRecorder::recordLocalLogSent($phone);
    }
}
