<?php

namespace Tests\Unit\Services\Otp;

use App\Exceptions\MissingTwilioOtpConfigurationException;
use App\Services\Otp\TwilioSmsOtpSender;
use Mockery;
use Tests\TestCase;
use Twilio\Rest\Client;

class TwilioSmsOtpSenderTest extends TestCase
{
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

        $this->assertSame('+15550000001', $capturedTo);
        $this->assertSame('MGxxxxxxxx', $capturedParams['messagingServiceSid']);
        $this->assertSame('Your Eventaat verification code is: 123456', $capturedParams['body']);
        $this->assertSame(300, $capturedParams['validityPeriod']);
    }

    public function test_send_throws_when_twilio_config_incomplete(): void
    {
        config([
            'eventaat-notifications.twilio.account_sid' => '',
            'eventaat-notifications.twilio.auth_token' => '',
            'eventaat-notifications.twilio.messaging_service_sid' => '',
        ]);

        $this->expectException(MissingTwilioOtpConfigurationException::class);

        (new TwilioSmsOtpSender(Mockery::mock(Client::class)))->send('+15550000001', '123456');
    }

    public function test_mask_phone_obscures_prefix(): void
    {
        $this->assertSame('*******0001', TwilioSmsOtpSender::maskPhone('+1 555 000 0001'));
    }
}
