<?php

namespace App\Services\Notifications\Providers;

use App\Models\BookingNotification;
use App\Services\Otp\MobileOtpService;
use App\Services\Otp\TwilioSmsOtpSender;
use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

final class TwilioSmsNotificationProvider implements NotificationProvider
{
    public const PROVIDER_NAME = 'twilio_sms';

    public function __construct(
        private readonly ?Client $twilioClient = null,
    ) {}

    public function identifier(): string
    {
        return self::PROVIDER_NAME;
    }

    public function send(BookingNotification $notification): NotificationProviderResult
    {
        $twilio = config('eventaat-notifications.twilio', []);
        $missing = self::missingRequiredKeys($twilio);
        if ($missing !== []) {
            return NotificationProviderResult::failure(
                'Booking SMS notification misconfigured: missing '.implode(', ', $missing).'.',
            );
        }

        $rawPhone = $notification->recipient_phone !== null ? trim((string) $notification->recipient_phone) : '';
        if ($rawPhone === '') {
            return NotificationProviderResult::failure('No recipient phone on booking notification.');
        }

        $phone = MobileOtpService::normalizePhone($rawPhone);
        if (! preg_match(MobileOtpService::OTP_PHONE_E164_REGEX, $phone)) {
            return NotificationProviderResult::failure('Recipient phone is not valid E.164.');
        }

        $body = trim((string) $notification->message);
        if ($body === '') {
            return NotificationProviderResult::failure('Notification message body is empty.');
        }

        $accountSid = (string) $twilio['account_sid'];
        $authToken = (string) $twilio['auth_token'];
        $messagingServiceSid = (string) $twilio['messaging_service_sid'];
        $validityPeriod = self::normalizeValidityPeriod((int) ($twilio['notification_validity_period'] ?? 36000));

        $client = $this->twilioClient ?? new Client($accountSid, $authToken);

        $params = [
            'messagingServiceSid' => $messagingServiceSid,
            'body' => $body,
            'validityPeriod' => $validityPeriod,
        ];

        try {
            $message = $client->messages->create($phone, $params);

            Log::info('Twilio SMS booking notification sent', [
                'provider' => self::PROVIDER_NAME,
                'booking_notification_id' => $notification->id,
                'event' => $notification->event,
                'to_masked' => TwilioSmsOtpSender::maskPhone($phone),
                'message_sid' => $message->sid ?? null,
            ]);

            return NotificationProviderResult::success($message->sid ?? null);
        } catch (TwilioException $e) {
            Log::warning('Twilio SMS booking notification failed', [
                'provider' => self::PROVIDER_NAME,
                'booking_notification_id' => $notification->id,
                'event' => $notification->event,
                'to_masked' => TwilioSmsOtpSender::maskPhone($phone),
                'twilio_status' => $e->getCode(),
            ]);

            return NotificationProviderResult::failure(
                'Twilio REST API rejected the booking notification SMS (code '.(string) $e->getCode().').',
            );
        } catch (\Throwable $e) {
            Log::warning('Twilio SMS booking notification failed', [
                'provider' => self::PROVIDER_NAME,
                'booking_notification_id' => $notification->id,
                'event' => $notification->event,
                'exception' => $e::class,
            ]);

            return NotificationProviderResult::failure('Unexpected error sending booking SMS.');
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
            return 36000;
        }

        return min($seconds, 36000);
    }
}
