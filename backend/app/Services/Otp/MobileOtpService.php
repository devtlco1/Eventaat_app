<?php

namespace App\Services\Otp;

use App\Models\MobileOtp;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MobileOtpService
{
    public function __construct(
        private readonly OtpSender $sender,
    ) {}

    public function request(string $phone, int $ttlSeconds = 300): MobileOtp
    {
        $otp = (string) random_int(100000, 999999);

        $record = MobileOtp::updateOrCreate(
            ['phone' => $phone, 'consumed_at' => null],
            [
                'otp_hash' => Hash::make($otp),
                'expires_at' => Carbon::now()->addSeconds($ttlSeconds),
                'attempts' => 0,
                'last_sent_at' => Carbon::now(),
            ],
        );

        $this->sender->send($phone, $otp);

        return $record;
    }

    public function verify(string $phone, string $otp): ?MobileOtp
    {
        /** @var MobileOtp|null $record */
        $record = MobileOtp::query()
            ->where('phone', $phone)
            ->whereNull('consumed_at')
            ->where('expires_at', '>=', Carbon::now())
            ->latest('id')
            ->first();

        if (! $record) {
            return null;
        }

        if (! Hash::check($otp, $record->otp_hash)) {
            $record->increment('attempts');
            return null;
        }

        $record->forceFill(['consumed_at' => Carbon::now()])->save();

        return $record;
    }

    public static function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        $phone = preg_replace('/\\s+/', '', $phone) ?? $phone;
        return $phone;
    }

    public static function mobileEmailForPhone(string $phone): string
    {
        return 'mobile_' . Str::lower(substr(sha1($phone), 0, 16)) . '@mobile.eventaat.test';
    }
}

