<?php

namespace App\Services\Otp;

use App\Exceptions\MissingTwilioWhatsAppOtpConfigurationException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

final class TwilioWhatsAppOtpSender implements OtpSender
{
    public function __construct(
        private readonly ?Client $twilioClient = null,
    ) {}

    public function send(string $phone, string $otp): void
    {
        $missing = self::missingRequiredKeys();
        if ($missing !== []) {
            OtpDeliveryAttemptRecorder::recordConfigurationFailure($phone, 'twilio_whatsapp', 'whatsapp', 'twilio');

            throw MissingTwilioWhatsAppOtpConfigurationException::forKeys($missing);
        }

        $twilio = config('eventaat-notifications.twilio', []);
        $accountSid = (string) $twilio['account_sid'];
        $authToken = (string) $twilio['auth_token'];

        $from = trim((string) config('eventaat-notifications.otp.twilio.whatsapp.from'));
        $contentSid = trim((string) config('eventaat-notifications.otp.twilio.whatsapp.otp_content_sid'));

        $client = $this->twilioClient ?? new Client($accountSid, $authToken);

        $toAddress = self::whatsappAddress($phone);

        // WhatsApp Authentication templates use Content API (contentSid + contentVariables).
        // Do not send a plain body — Twilio rejects auth-template sends that mix body text.
        // validityPeriod is omitted here: Twilio documents queue TTL primarily for classic SMS/MMS;
        // Content-template WhatsApp flows do not consistently expose the same parameter on create().
        $params = [
            'from' => $from,
            'contentSid' => $contentSid,
            'contentVariables' => json_encode(['1' => $otp], JSON_THROW_ON_ERROR),
        ];

        $attempt = OtpDeliveryAttemptRecorder::beginPending($phone, 'twilio_whatsapp', 'whatsapp', 'twilio');

        try {
            $message = $client->messages->create($toAddress, $params);
            OtpDeliveryAttemptRecorder::markSent($attempt, $message->sid ?? null);

            Log::info('Twilio WhatsApp OTP sent', [
                'provider' => 'twilio_whatsapp',
                'to' => TwilioSmsOtpSender::maskPhone($toAddress),
                'message_sid' => $message->sid ?? null,
            ]);
        } catch (TwilioException $e) {
            OtpDeliveryAttemptRecorder::markFailed(
                $attempt,
                (string) $e->getCode(),
                'Twilio REST API rejected the WhatsApp send.',
                ['twilio_exception' => true],
            );

            Log::error('Twilio WhatsApp OTP send failed', [
                'provider' => 'twilio_whatsapp',
                'to' => TwilioSmsOtpSender::maskPhone($toAddress),
                'twilio_status' => $e->getCode(),
            ]);

            throw new ServiceUnavailableHttpException(
                null,
                'Unable to send verification code. Please try again later.',
                $e,
            );
        } catch (\Throwable $e) {
            OtpDeliveryAttemptRecorder::markFailed(
                $attempt,
                'provider_error',
                'Unexpected error sending WhatsApp OTP.',
                ['exception' => $e::class],
            );

            Log::error('Twilio WhatsApp OTP send failed', [
                'provider' => 'twilio_whatsapp',
                'to' => TwilioSmsOtpSender::maskPhone($toAddress),
            ]);

            throw new ServiceUnavailableHttpException(
                null,
                'Unable to send verification code. Please try again later.',
                $e,
            );
        }
    }

    private static function whatsappAddress(string $e164Phone): string
    {
        $e164Phone = trim($e164Phone);

        return str_starts_with($e164Phone, 'whatsapp:')
            ? $e164Phone
            : 'whatsapp:'.$e164Phone;
    }

    /**
     * @return list<string>
     */
    private static function missingRequiredKeys(): array
    {
        $twilio = config('eventaat-notifications.twilio', []);
        $missing = [];

        foreach (['account_sid', 'auth_token'] as $key) {
            $value = isset($twilio[$key]) ? trim((string) $twilio[$key]) : '';
            if ($value === '') {
                $missing[] = match ($key) {
                    'account_sid' => 'TWILIO_ACCOUNT_SID',
                    'auth_token' => 'TWILIO_AUTH_TOKEN',
                    default => $key,
                };
            }
        }

        $from = trim((string) config('eventaat-notifications.otp.twilio.whatsapp.from'));
        if ($from === '') {
            $missing[] = 'TWILIO_WHATSAPP_FROM';
        }

        $contentSid = trim((string) config('eventaat-notifications.otp.twilio.whatsapp.otp_content_sid'));
        if ($contentSid === '') {
            $missing[] = 'TWILIO_WHATSAPP_OTP_CONTENT_SID';
        }

        return $missing;
    }
}
