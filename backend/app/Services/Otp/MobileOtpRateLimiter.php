<?php

namespace App\Services\Otp;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

final class MobileOtpRateLimiter
{
    public function responseIfRequestLimited(string $phone): ?JsonResponse
    {
        $phoneKey = self::phoneCacheKeySuffix($phone);

        $cooldown = max(0, (int) config('eventaat-notifications.otp_rate_limit.request_cooldown_seconds', 60));
        if ($cooldown > 0) {
            $key = 'eventaat:otp:request:cooldown:'.$phoneKey;
            if (RateLimiter::tooManyAttempts($key, 1)) {
                $retryAfter = RateLimiter::availableIn($key);

                Log::warning('OTP request blocked by cooldown', [
                    'reason' => 'request_cooldown',
                    'phone' => self::maskPhone($phone),
                ]);

                return self::tooManyRequestsJson($retryAfter, 'Please wait before requesting another verification code.');
            }
        }

        $maxHourly = max(1, (int) config('eventaat-notifications.otp_rate_limit.request_max_per_hour', 5));
        $hourlyKey = 'eventaat:otp:request:hourly:'.$phoneKey;
        if (RateLimiter::tooManyAttempts($hourlyKey, $maxHourly)) {
            $retryAfter = RateLimiter::availableIn($hourlyKey);

            Log::warning('OTP request blocked by hourly limit', [
                'reason' => 'request_hourly',
                'phone' => self::maskPhone($phone),
            ]);

            return self::tooManyRequestsJson($retryAfter, 'Too many verification code requests. Try again later.');
        }

        return null;
    }

    public function hitSuccessfulRequest(string $phone): void
    {
        $phoneKey = self::phoneCacheKeySuffix($phone);

        $cooldown = max(0, (int) config('eventaat-notifications.otp_rate_limit.request_cooldown_seconds', 60));
        if ($cooldown > 0) {
            RateLimiter::hit('eventaat:otp:request:cooldown:'.$phoneKey, $cooldown);
        }

        RateLimiter::hit('eventaat:otp:request:hourly:'.$phoneKey, 3600);
    }

    public function responseIfVerifyLimited(string $phone): ?JsonResponse
    {
        $phoneKey = self::phoneCacheKeySuffix($phone);
        $maxAttempts = max(1, (int) config('eventaat-notifications.otp_rate_limit.verify_max_attempts', 5));
        $decaySeconds = self::verifyDecaySeconds();

        $key = 'eventaat:otp:verify:fail:'.$phoneKey;
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);

            Log::warning('OTP verify blocked after failed attempts', [
                'reason' => 'verify_failures',
                'phone' => self::maskPhone($phone),
            ]);

            return self::tooManyRequestsJson($retryAfter, 'Too many verification attempts. Try again later.');
        }

        return null;
    }

    public function hitVerifyFailure(string $phone): void
    {
        $phoneKey = self::phoneCacheKeySuffix($phone);

        RateLimiter::hit('eventaat:otp:verify:fail:'.$phoneKey, self::verifyDecaySeconds());
    }

    private static function verifyDecaySeconds(): int
    {
        $minutes = max(1, (int) config('eventaat-notifications.otp_rate_limit.verify_decay_minutes', 10));

        return $minutes * 60;
    }

    public function clearVerifyFailures(string $phone): void
    {
        $phoneKey = self::phoneCacheKeySuffix($phone);
        RateLimiter::clear('eventaat:otp:verify:fail:'.$phoneKey);
    }

    private static function phoneCacheKeySuffix(string $e164Phone): string
    {
        return hash('sha256', $e164Phone);
    }

    private static function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if (strlen($digits) < 4) {
            return '***';
        }

        return str_repeat('*', max(0, strlen($digits) - 4)).substr($digits, -4);
    }

    private static function tooManyRequestsJson(int $retryAfterSeconds, string $message): JsonResponse
    {
        $retryAfterSeconds = max(1, $retryAfterSeconds);

        return response()->json([
            'message' => $message,
            'retry_after' => $retryAfterSeconds,
        ], 429)->withHeaders([
            'Retry-After' => (string) $retryAfterSeconds,
        ]);
    }
}
