<?php

namespace Tests\Unit\Services\Otp;

use App\Exceptions\MissingTwilioWhatsAppOtpConfigurationException;
use App\Models\OtpDeliveryAttempt;
use App\Services\Otp\TwilioWhatsAppOtpSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\TestCase;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class TwilioWhatsAppOtpSenderTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_send_uses_whatsapp_addresses_content_sid_and_variables(): void
    {
        config([
            'eventaat-notifications.twilio.account_sid' => 'ACxxxxxxxx',
            'eventaat-notifications.twilio.auth_token' => 'token_redacted',
            'eventaat-notifications.otp.twilio.whatsapp.from' => 'whatsapp:+15559382160',
            'eventaat-notifications.otp.twilio.whatsapp.otp_content_sid' => 'HXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
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

        $sender = new TwilioWhatsAppOtpSender($client);
        $sender->send('+9647700001781', '123456');

        $this->assertDatabaseHas('otp_delivery_attempts', [
            'driver' => 'twilio_whatsapp',
            'channel' => 'whatsapp',
            'provider' => 'twilio',
            'status' => OtpDeliveryAttempt::STATUS_SENT,
            'provider_message_sid' => 'SM_test_sid',
        ]);

        $this->assertSame('whatsapp:+9647700001781', $capturedTo);
        $this->assertSame('whatsapp:+15559382160', $capturedParams['from']);
        $this->assertSame('HXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', $capturedParams['contentSid']);
        $this->assertSame('{"1":"123456"}', $capturedParams['contentVariables']);
        $this->assertArrayNotHasKey('body', $capturedParams);
    }

    public function test_send_throws_when_whatsapp_config_incomplete(): void
    {
        config([
            'eventaat-notifications.twilio.account_sid' => '',
            'eventaat-notifications.twilio.auth_token' => '',
            'eventaat-notifications.otp.twilio.whatsapp.from' => '',
            'eventaat-notifications.otp.twilio.whatsapp.otp_content_sid' => '',
        ]);

        try {
            (new TwilioWhatsAppOtpSender(Mockery::mock(Client::class)))->send('+9647700001781', '123456');
            $this->fail('Expected MissingTwilioWhatsAppOtpConfigurationException.');
        } catch (MissingTwilioWhatsAppOtpConfigurationException) {
            $this->assertDatabaseHas('otp_delivery_attempts', [
                'phone_hash' => hash('sha256', '+9647700001781'),
                'status' => OtpDeliveryAttempt::STATUS_FAILED,
                'error_code' => 'configuration',
            ]);
        }
    }

    public function test_failed_whatsapp_send_records_failed_audit_row(): void
    {
        config([
            'eventaat-notifications.twilio.account_sid' => 'ACxxxxxxxx',
            'eventaat-notifications.twilio.auth_token' => 'token_redacted',
            'eventaat-notifications.otp.twilio.whatsapp.from' => 'whatsapp:+15550009999',
            'eventaat-notifications.otp.twilio.whatsapp.otp_content_sid' => 'HXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        ]);

        $messages = Mockery::mock();
        $messages->shouldReceive('create')
            ->once()
            ->andThrow(new TwilioException('simulated_whatsapp_failure'));

        $client = Mockery::mock(Client::class);
        $client->messages = $messages;

        try {
            (new TwilioWhatsAppOtpSender($client))->send('+9647700001999', '111222');
            $this->fail('Expected ServiceUnavailableHttpException.');
        } catch (ServiceUnavailableHttpException) {
            //
        }

        $this->assertDatabaseHas('otp_delivery_attempts', [
            'phone_hash' => hash('sha256', '+9647700001999'),
            'driver' => 'twilio_whatsapp',
            'status' => OtpDeliveryAttempt::STATUS_FAILED,
        ]);
        $this->assertDatabaseMissing('otp_delivery_attempts', [
            'error_message' => '111222',
        ]);
    }
}
