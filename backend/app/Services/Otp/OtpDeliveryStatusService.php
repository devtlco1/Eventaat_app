<?php

namespace App\Services\Otp;

use App\Models\OtpDeliveryAttempt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class OtpDeliveryStatusService
{
    /**
     * Apply Twilio status callback payload to an existing OTP delivery attempt (by MessageSid).
     * Ignores To/From and never persists OTP codes or full phone numbers.
     *
     * @param  array<string, mixed>  $params
     */
    public function handleCallback(array $params): void
    {
        $messageSid = $this->stringParam($params, 'MessageSid');
        if ($messageSid === null || $messageSid === '') {
            Log::warning('Twilio OTP status webhook missing MessageSid');

            return;
        }

        $sidKey = Str::limit($messageSid, 64, '');
        $attempt = OtpDeliveryAttempt::query()
            ->where('provider_message_sid', $sidKey)
            ->first();

        if ($attempt === null) {
            Log::warning('Twilio OTP status webhook unknown MessageSid', [
                'provider_message_sid' => $sidKey,
            ]);

            return;
        }

        $rawStatus = $this->resolveRawTwilioStatus($params);
        $errorCode = $this->stringParam($params, 'ErrorCode');
        $errorMessage = $this->stringParam($params, 'ErrorMessage');
        $accountSid = $this->stringParam($params, 'AccountSid');

        $internal = $this->normalizeTwilioStatus($rawStatus);

        $mergedMeta = array_merge($attempt->metadata ?? [], [
            'provider_status' => $rawStatus ?? '',
            'callback_received_at' => now()->toIso8601String(),
        ]);

        if ($accountSid !== null && $accountSid !== '') {
            $mergedMeta['twilio_account_sid'] = Str::limit($accountSid, 64, '');
        }
        if ($errorCode !== null && $errorCode !== '') {
            $mergedMeta['error_code'] = Str::limit($errorCode, 64, '');
        }
        if ($errorMessage !== null && $errorMessage !== '') {
            $mergedMeta['error_message'] = Str::limit($errorMessage, 500, '…');
        }

        if ($internal === null && ($rawStatus !== null && $rawStatus !== '')) {
            $attempt->forceFill(['metadata' => $mergedMeta])->save();

            return;
        }

        if ($internal === OtpDeliveryAttempt::STATUS_PENDING) {
            $attempt->forceFill(['metadata' => $mergedMeta])->save();

            return;
        }

        match ($internal) {
            OtpDeliveryAttempt::STATUS_SENT => $this->applySent($attempt, $mergedMeta),
            OtpDeliveryAttempt::STATUS_DELIVERED => $this->applyDelivered($attempt, $mergedMeta),
            OtpDeliveryAttempt::STATUS_UNDELIVERED => $this->applyUndelivered($attempt, $mergedMeta, $errorCode, $errorMessage),
            OtpDeliveryAttempt::STATUS_FAILED => $this->applyFailed($attempt, $mergedMeta, $errorCode, $errorMessage),
            default => $attempt->forceFill(['metadata' => $mergedMeta])->save(),
        };
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function resolveRawTwilioStatus(array $params): ?string
    {
        $messageStatus = $this->stringParam($params, 'MessageStatus');
        if ($messageStatus !== null && $messageStatus !== '') {
            return strtolower(trim($messageStatus));
        }

        $smsStatus = $this->stringParam($params, 'SmsStatus');
        if ($smsStatus !== null && $smsStatus !== '') {
            return strtolower(trim($smsStatus));
        }

        return null;
    }

    private function normalizeTwilioStatus(?string $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        return match ($raw) {
            'queued', 'accepted', 'scheduled', 'sending', 'receiving' => OtpDeliveryAttempt::STATUS_PENDING,
            'sent' => OtpDeliveryAttempt::STATUS_SENT,
            'delivered', 'received', 'read' => OtpDeliveryAttempt::STATUS_DELIVERED,
            'undelivered' => OtpDeliveryAttempt::STATUS_UNDELIVERED,
            'failed' => OtpDeliveryAttempt::STATUS_FAILED,
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function applySent(OtpDeliveryAttempt $attempt, array $metadata): void
    {
        if (! in_array($attempt->status, [OtpDeliveryAttempt::STATUS_PENDING, OtpDeliveryAttempt::STATUS_SENT], true)) {
            $attempt->forceFill(['metadata' => $metadata])->save();

            return;
        }

        $attempt->forceFill([
            'status' => OtpDeliveryAttempt::STATUS_SENT,
            'metadata' => $metadata,
            'error_code' => null,
            'error_message' => null,
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function applyDelivered(OtpDeliveryAttempt $attempt, array $metadata): void
    {
        if (in_array($attempt->status, [OtpDeliveryAttempt::STATUS_FAILED, OtpDeliveryAttempt::STATUS_UNDELIVERED], true)) {
            $attempt->forceFill(['metadata' => $metadata])->save();

            return;
        }

        $attempt->forceFill([
            'status' => OtpDeliveryAttempt::STATUS_DELIVERED,
            'metadata' => $metadata,
            'error_code' => null,
            'error_message' => null,
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function applyUndelivered(OtpDeliveryAttempt $attempt, array $metadata, ?string $errorCode, ?string $errorMessage): void
    {
        if ($attempt->status === OtpDeliveryAttempt::STATUS_DELIVERED) {
            $attempt->forceFill(['metadata' => $metadata])->save();

            return;
        }

        $attempt->forceFill([
            'status' => OtpDeliveryAttempt::STATUS_UNDELIVERED,
            'metadata' => $metadata,
            'error_code' => $errorCode !== null && $errorCode !== ''
                ? Str::limit($errorCode, 64, '')
                : null,
            'error_message' => $errorMessage !== null && $errorMessage !== ''
                ? Str::limit($errorMessage, 2000, '…')
                : null,
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function applyFailed(OtpDeliveryAttempt $attempt, array $metadata, ?string $errorCode, ?string $errorMessage): void
    {
        if ($attempt->status === OtpDeliveryAttempt::STATUS_DELIVERED) {
            $attempt->forceFill(['metadata' => $metadata])->save();

            return;
        }

        $attempt->forceFill([
            'status' => OtpDeliveryAttempt::STATUS_FAILED,
            'metadata' => $metadata,
            'error_code' => $errorCode !== null && $errorCode !== ''
                ? Str::limit($errorCode, 64, '')
                : null,
            'error_message' => $errorMessage !== null && $errorMessage !== ''
                ? Str::limit($errorMessage, 2000, '…')
                : null,
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function stringParam(array $params, string $key): ?string
    {
        if (! isset($params[$key])) {
            return null;
        }

        $value = $params[$key];
        if (is_array($value)) {
            return null;
        }

        $s = trim((string) $value);

        return $s === '' ? null : $s;
    }
}
