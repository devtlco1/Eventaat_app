<?php

namespace Tests\Unit\Services\Otp;

use App\Exceptions\MissingTwilioWhatsAppOtpConfigurationException;
use App\Services\Otp\TwilioWhatsAppOtpSender;
use Mockery;
use Tests\TestCase;
use Twilio\Rest\Client;

class TwilioWhatsAppOtpSenderTest extends TestCase
{
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

        $this->expectException(MissingTwilioWhatsAppOtpConfigurationException::class);

        (new TwilioWhatsAppOtpSender(Mockery::mock(Client::class)))->send('+9647700001781', '123456');
    }
}
