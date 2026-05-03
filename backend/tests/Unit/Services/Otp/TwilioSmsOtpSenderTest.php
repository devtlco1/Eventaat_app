<?php

namespace Tests\Unit\Services\Otp;

use App\Exceptions\MissingTwilioOtpConfigurationException;
use App\Models\OtpDeliveryAttempt;
use App\Services\Otp\TwilioSmsOtpSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\TestCase;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class TwilioSmsOtpSenderTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_send_uses_messaging_service_sid_body_and_validity_period(): void
    {
        config([
            'eventaat-notifications.twilio.account_sid' => 'ACxxxxxxxx',
            'eventaat-notifications.twilio.auth_token' => 'token_redacted',
            'eventaat-notifications.twilio.messaging_service_sid' => 'MGxxxxxxxx',
            'eventaat-notifications.twilio.otp_validity_period' => 300,
        ]);

        $capturedTo = null;
        $capturedParams = null;

        $messages = Mockery::mock();
        $messages->shouldReceive('create')
            ->once()
            ->withArgs(function (string $to, array $params) use (&$capturedTo, &$capturedParams) {
                $capturedTo = $to;
                $capturedParams = $params;

                return true;
            })
            ->andReturn((object) ['sid' => 'SM_test_sid']);

        $client = Mockery::mock(Client::class);
        $client->messages = $messages;

        $sender = new TwilioSmsOtpSender($client);
        $sender->send('+15550000001', '123456');

        $this->assertDatabaseHas('otp_delivery_attempts', [
            'driver' => 'twilio_sms',
            'channel' => 'sms',
            'provider' => 'twilio',
            'status' => OtpDeliveryAttempt::STATUS_SENT,
            'provider_message_sid' => 'SM_test_sid',
        ]);

        $this->assertSame('+15550000001', $capturedTo);
        $this->assertSame('MGxxxxxxxx', $capturedParams['messagingServiceSid']);
        $this->assertSame('Eventaat code: 123456. Do not share this code.', $capturedParams['body']);
        $this->assertSame(300, $capturedParams['validityPeriod']);
    }

    public function test_send_throws_when_twilio_config_incomplete(): void
    {
        config([
            'eventaat-notifications.twilio.account_sid' => '',
            'eventaat-notifications.twilio.auth_token' => '',
            'eventaat-notifications.twilio.messaging_service_sid' => '',
        ]);

        try {
            (new TwilioSmsOtpSender(Mockery::mock(Client::class)))->send('+15550000001', '123456');
            $this->fail('Expected MissingTwilioOtpConfigurationException.');
        } catch (MissingTwilioOtpConfigurationException) {
            $this->assertDatabaseHas('otp_delivery_attempts', [
                'phone_hash' => hash('sha256', '+15550000001'),
                'status' => OtpDeliveryAttempt::STATUS_FAILED,
                'error_code' => 'configuration',
            ]);
        }
    }

    public function test_failed_twilio_send_records_failed_audit_row(): void
    {
        config([
            'eventaat-notifications.twilio.account_sid' => 'ACxxxxxxxx',
            'eventaat-notifications.twilio.auth_token' => 'token_redacted',
            'eventaat-notifications.twilio.messaging_service_sid' => 'MGxxxxxxxx',
            'eventaat-notifications.twilio.otp_validity_period' => 300,
        ]);

        $messages = Mockery::mock();
        $messages->shouldReceive('create')
            ->once()
            ->andThrow(new TwilioException('simulated_twilio_failure'));

        $client = Mockery::mock(Client::class);
        $client->messages = $messages;

        $sender = new TwilioSmsOtpSender($client);

        try {
            $sender->send('+15550000002', '654321');
            $this->fail('Expected ServiceUnavailableHttpException.');
        } catch (ServiceUnavailableHttpException) {
            //
        }

        $this->assertDatabaseHas('otp_delivery_attempts', [
            'phone_hash' => hash('sha256', '+15550000002'),
            'driver' => 'twilio_sms',
            'status' => OtpDeliveryAttempt::STATUS_FAILED,
        ]);
        $this->assertDatabaseMissing('otp_delivery_attempts', [
            'error_message' => '654321',
        ]);
    }

    public function test_mask_phone_obscures_prefix(): void
    {
        $this->assertSame('*******0001', TwilioSmsOtpSender::maskPhone('+1 555 000 0001'));
    }
}
