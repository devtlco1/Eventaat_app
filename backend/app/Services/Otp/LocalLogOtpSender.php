<?php

namespace App\Services\Otp;

use Illuminate\Support\Facades\Log;

class LocalLogOtpSender implements OtpSender
{
    public function send(string $phone, string $otp): void
    {
        Log::info('Mobile OTP (local/dev)', [
            'phone_masked' => TwilioSmsOtpSender::maskPhone($phone),
        ]);

        OtpDeliveryAttemptRecorder::recordLocalLogSent($phone);
    }
}
