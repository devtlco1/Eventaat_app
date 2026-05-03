<?php

namespace App\Services\Otp;

use App\Exceptions\MissingTwilioOtpConfigurationException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

final class TwilioSmsOtpSender implements OtpSender
{
    public function __construct(
        private readonly ?Client $twilioClient = null,
    ) {
        if ($this->twilioClient === null) {
            self::assertTwilioConfigured();
        }
    }

    public function send(string $phone, string $otp): void
    {
        $twilio = config('eventaat-notifications.twilio', []);
        $missing = self::missingRequiredKeys($twilio);
        if ($missing !== []) {
            throw MissingTwilioOtpConfigurationException::forKeys($missing);
        }

        $accountSid = (string) $twilio['account_sid'];
        $authToken = (string) $twilio['auth_token'];
        $messagingServiceSid = (string) $twilio['messaging_service_sid'];
        $validityPeriod = self::normalizeValidityPeriod((int) $twilio['otp_validity_period']);

        $client = $this->twilioClient ?? new Client($accountSid, $authToken);
        $body = sprintf('Your Eventaat verification code is: %s', $otp);

        $params = [
            'messagingServiceSid' => $messagingServiceSid,
            'body' => $body,
            'validityPeriod' => $validityPeriod,
        ];

        try {
            $message = $client->messages->create($phone, $params);
        } catch (TwilioException $e) {
            Log::error('Twilio SMS OTP send failed', [
                'provider' => 'twilio_sms',
                'to' => self::maskPhone($phone),
                'twilio_status' => $e->getCode(),
            ]);

            throw new ServiceUnavailableHttpException(
                null,
                'Unable to send verification code. Please try again later.',
                $e,
            );
        } catch (\Throwable $e) {
            Log::error('Twilio SMS OTP send failed', [
                'provider' => 'twilio_sms',
                'to' => self::maskPhone($phone),
            ]);

            throw new ServiceUnavailableHttpException(
                null,
                'Unable to send verification code. Please try again later.',
                $e,
            );
        }

        Log::info('Twilio SMS OTP sent', [
            'provider' => 'twilio_sms',
            'to' => self::maskPhone($phone),
            'message_sid' => $message->sid ?? null,
        ]);
    }

    private static function assertTwilioConfigured(): void
    {
        $twilio = config('eventaat-notifications.twilio', []);
        $missing = self::missingRequiredKeys($twilio);
        if ($missing !== []) {
            throw MissingTwilioOtpConfigurationException::forKeys($missing);
        }
    }

    /**
     * @param  array<string, mixed>  $twilio
     * @return list<string>
     */
    private static function missingRequiredKeys(array $twilio): array
    {
        $missing = [];
        foreach (['account_sid', 'auth_token', 'messaging_service_sid'] as $key) {
            $value = isset($twilio[$key]) ? trim((string) $twilio[$key]) : '';
            if ($value === '') {
                $missing[] = match ($key) {
                    'account_sid' => 'TWILIO_ACCOUNT_SID',
                    'auth_token' => 'TWILIO_AUTH_TOKEN',
                    'messaging_service_sid' => 'TWILIO_MESSAGING_SERVICE_SID',
                    default => $key,
                };
            }
        }

        return $missing;
    }

    private static function normalizeValidityPeriod(int $seconds): int
    {
        if ($seconds < 1) {
            return 300;
        }

        return min($seconds, 36000);
    }

    public static function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if (strlen($digits) < 4) {
            return '***';
        }

        return str_repeat('*', max(0, strlen($digits) - 4)).substr($digits, -4);
    }
}
