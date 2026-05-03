<?php

namespace Tests\Feature;

use App\Models\OtpDeliveryAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Twilio\Security\RequestValidator;

class TwilioOtpStatusWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'eventaat-notifications.twilio.auth_token' => 'unit_test_twilio_auth_token',
            'eventaat-notifications.twilio.webhook_secret' => '',
        ]);
    }

    /**
     * @param  array<string, scalar|null>  $params
     */
    private function signedTwilioPost(array $params): TestResponse
    {
        $absoluteUrl = route('webhooks.twilio.otp-status', [], true);
        $validator = new RequestValidator(config('eventaat-notifications.twilio.auth_token'));
        $signature = $validator->computeSignature($absoluteUrl, $params);

        return $this->post('/api/webhooks/twilio/otp-status', $params, [
            'HTTP_X_TWILIO_SIGNATURE' => $signature,
        ]);
    }

    private function makeSentAttempt(string $messageSid = 'SM_WEBHOOK_TEST_01'): OtpDeliveryAttempt
    {
        return OtpDeliveryAttempt::create([
            'phone_hash' => hash('sha256', '+15550009999'),
            'phone_masked' => '*******9999',
            'driver' => 'twilio_sms',
            'channel' => 'sms',
            'provider' => 'twilio',
            'provider_message_sid' => $messageSid,
            'status' => OtpDeliveryAttempt::STATUS_SENT,
            'error_code' => null,
            'error_message' => null,
            'metadata' => null,
            'created_at' => now(),
        ]);
    }

    public function test_valid_webhook_updates_sent_attempt_to_delivered(): void
    {
        $attempt = $this->makeSentAttempt();

        $response = $this->signedTwilioPost([
            'MessageSid' => $attempt->provider_message_sid,
            'MessageStatus' => 'delivered',
            'AccountSid' => 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        ]);

        $response->assertOk();

        $attempt->refresh();

        $this->assertSame(OtpDeliveryAttempt::STATUS_DELIVERED, $attempt->status);
        $this->assertSame('delivered', $attempt->metadata['provider_status']);
        $this->assertArrayHasKey('callback_received_at', $attempt->metadata);
        $this->assertSame('ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', $attempt->metadata['twilio_account_sid']);
    }

    public function test_valid_webhook_updates_attempt_to_failed_with_error_fields(): void
    {
        $attempt = $this->makeSentAttempt('SM_WEBHOOK_FAILED');

        $response = $this->signedTwilioPost([
            'MessageSid' => $attempt->provider_message_sid,
            'MessageStatus' => 'failed',
            'ErrorCode' => '30007',
            'ErrorMessage' => 'Carrier violation',
            'AccountSid' => 'ACaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        ]);

        $response->assertOk();

        $attempt->refresh();

        $this->assertSame(OtpDeliveryAttempt::STATUS_FAILED, $attempt->status);
        $this->assertSame('30007', $attempt->error_code);
        $this->assertStringContainsString('Carrier violation', (string) $attempt->error_message);
        $this->assertSame('30007', $attempt->metadata['error_code']);
    }

    public function test_unknown_message_sid_returns_200_and_does_not_create_attempt(): void
    {
        $before = OtpDeliveryAttempt::query()->count();

        $response = $this->signedTwilioPost([
            'MessageSid' => 'SM_UNKNOWN_NOT_IN_DB',
            'MessageStatus' => 'delivered',
        ]);

        $response->assertOk();

        $this->assertSame($before, OtpDeliveryAttempt::query()->count());
    }

    public function test_invalid_twilio_signature_returns_403(): void
    {
        $attempt = $this->makeSentAttempt();

        $response = $this->post('/api/webhooks/twilio/otp-status', [
            'MessageSid' => $attempt->provider_message_sid,
            'MessageStatus' => 'delivered',
        ], [
            'HTTP_X_TWILIO_SIGNATURE' => 'invalid_signature_value',
        ]);

        $response->assertForbidden();

        $attempt->refresh();
        $this->assertSame(OtpDeliveryAttempt::STATUS_SENT, $attempt->status);
    }

    public function test_invalid_webhook_secret_returns_403_when_auth_token_empty(): void
    {
        config([
            'eventaat-notifications.twilio.auth_token' => '',
            'eventaat-notifications.twilio.webhook_secret' => 'expected_secret_value',
        ]);

        $attempt = $this->makeSentAttempt();

        $response = $this->post('/api/webhooks/twilio/otp-status', [
            'MessageSid' => $attempt->provider_message_sid,
            'MessageStatus' => 'delivered',
        ], [
            'HTTP_X_Eventaat-Webhook-Secret' => 'wrong_secret',
        ]);

        $response->assertForbidden();

        $attempt->refresh();
        $this->assertSame(OtpDeliveryAttempt::STATUS_SENT, $attempt->status);
    }

    public function test_webhook_secret_header_accepted_when_auth_token_empty(): void
    {
        config([
            'eventaat-notifications.twilio.auth_token' => '',
            'eventaat-notifications.twilio.webhook_secret' => 'shared_secret_ok',
        ]);

        $attempt = $this->makeSentAttempt();

        $response = $this->post('/api/webhooks/twilio/otp-status', [
            'MessageSid' => $attempt->provider_message_sid,
            'MessageStatus' => 'delivered',
        ], [
            'HTTP_X_Eventaat-Webhook-Secret' => 'shared_secret_ok',
        ]);

        $response->assertOk();

        $attempt->refresh();
        $this->assertSame(OtpDeliveryAttempt::STATUS_DELIVERED, $attempt->status);
    }

    public function test_webhook_metadata_never_contains_full_phone_from_payload(): void
    {
        $attempt = $this->makeSentAttempt('SM_META_PRIVACY');

        $fullNational = '+966501234567';

        $response = $this->signedTwilioPost([
            'MessageSid' => $attempt->provider_message_sid,
            'MessageStatus' => 'sent',
            'To' => $fullNational,
            'From' => '+15551234567',
            'Body' => 'Bad actors might put OTP here 999888 — ignored',
        ]);

        $response->assertOk();

        $attempt->refresh();

        $encoded = json_encode($attempt->metadata, JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString($fullNational, $encoded);
        $this->assertStringNotContainsString('15551234567', $encoded);
        $this->assertStringNotContainsString('999888', $encoded);
    }

    public function test_unauthenticated_configuration_returns_403(): void
    {
        config([
            'eventaat-notifications.twilio.auth_token' => '',
            'eventaat-notifications.twilio.webhook_secret' => '',
        ]);

        $attempt = $this->makeSentAttempt();

        $response = $this->post('/api/webhooks/twilio/otp-status', [
            'MessageSid' => $attempt->provider_message_sid,
            'MessageStatus' => 'delivered',
        ]);

        $response->assertForbidden();

        $attempt->refresh();
        $this->assertSame(OtpDeliveryAttempt::STATUS_SENT, $attempt->status);
    }
}
