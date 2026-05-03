<?php

namespace App\Services\Otp;

use App\Models\OtpDeliveryAttempt;
use Illuminate\Support\Str;

final class OtpDeliveryAttemptRecorder
{
    public static function recordLocalLogSent(string $phone): void
    {
        self::insertRow(
            phone: $phone,
            driver: 'log',
            channel: 'log',
            provider: 'local_log',
            status: OtpDeliveryAttempt::STATUS_SENT,
            providerMessageSid: null,
            errorCode: null,
            errorMessage: null,
            metadata: null,
        );
    }

    /**
     * Failed row for misconfiguration before any provider API call (no pending row).
     */
    public static function recordConfigurationFailure(
        string $phone,
        string $driver,
        ?string $channel,
        string $provider,
    ): void {
        self::insertRow(
            phone: $phone,
            driver: $driver,
            channel: $channel,
            provider: $provider,
            status: OtpDeliveryAttempt::STATUS_FAILED,
            providerMessageSid: null,
            errorCode: 'configuration',
            errorMessage: 'OTP provider configuration incomplete or invalid.',
            metadata: null,
        );
    }

    public static function beginPending(string $phone, string $driver, string $channel, string $provider): OtpDeliveryAttempt
    {
        $now = now();

        return OtpDeliveryAttempt::create([
            'phone_hash' => self::phoneHash($phone),
            'phone_masked' => TwilioSmsOtpSender::maskPhone($phone),
            'driver' => $driver,
            'channel' => $channel,
            'provider' => $provider,
            'provider_message_sid' => null,
            'status' => OtpDeliveryAttempt::STATUS_PENDING,
            'error_code' => null,
            'error_message' => null,
            'metadata' => null,
            'created_at' => $now,
        ]);
    }

    public static function markSent(OtpDeliveryAttempt $attempt, ?string $providerMessageSid): void
    {
        $attempt->forceFill([
            'status' => OtpDeliveryAttempt::STATUS_SENT,
            'provider_message_sid' => $providerMessageSid !== null && $providerMessageSid !== ''
                ? Str::limit($providerMessageSid, 64, '')
                : null,
            'error_code' => null,
            'error_message' => null,
        ])->save();
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public static function markFailed(
        OtpDeliveryAttempt $attempt,
        ?string $errorCode,
        ?string $errorMessage,
        ?array $metadata = null,
    ): void {
        $attempt->forceFill([
            'status' => OtpDeliveryAttempt::STATUS_FAILED,
            'error_code' => $errorCode !== null && $errorCode !== ''
                ? Str::limit($errorCode, 64, '')
                : null,
            'error_message' => $errorMessage !== null && $errorMessage !== ''
                ? Str::limit($errorMessage, 2000, '…')
                : null,
            'metadata' => $metadata,
        ])->save();
    }

    private static function phoneHash(string $phone): string
    {
        return hash('sha256', $phone);
    }

    private static function insertRow(
        string $phone,
        string $driver,
        ?string $channel,
        ?string $provider,
        string $status,
        ?string $providerMessageSid,
        ?string $errorCode,
        ?string $errorMessage,
        ?array $metadata,
    ): void {
        OtpDeliveryAttempt::create([
            'phone_hash' => self::phoneHash($phone),
            'phone_masked' => TwilioSmsOtpSender::maskPhone($phone),
            'driver' => $driver,
            'channel' => $channel,
            'provider' => $provider,
            'provider_message_sid' => $providerMessageSid !== null && $providerMessageSid !== ''
                ? Str::limit($providerMessageSid, 64, '')
                : null,
            'status' => $status,
            'error_code' => $errorCode !== null && $errorCode !== ''
                ? Str::limit($errorCode, 64, '')
                : null,
            'error_message' => $errorMessage !== null && $errorMessage !== ''
                ? Str::limit($errorMessage, 2000, '…')
                : null,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
