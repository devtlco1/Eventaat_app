<?php

namespace Tests\Feature;

use App\Exceptions\MissingTwilioOtpConfigurationException;
use App\Models\OtpDeliveryAttempt;
use App\Models\User;
use App\Services\Otp\OtpSender;
use Database\Seeders\RolesAndTestUsersSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class OtpDeliveryAuditTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'eventaat-notifications.otp.driver' => 'log',
            'eventaat-notifications.otp_rate_limit.request_cooldown_seconds' => 0,
            'eventaat-notifications.otp_rate_limit.request_max_per_hour' => 50,
        ]);

        $this->seed(RolesAndTestUsersSeeder::class);
    }

    public function test_request_otp_with_log_driver_records_delivery_attempt(): void
    {
        $phone = '+15558887701';

        $this->postJson('/api/mobile/auth/request-otp', ['phone' => $phone])->assertOk();

        $this->assertDatabaseHas('otp_delivery_attempts', [
            'phone_hash' => hash('sha256', $phone),
            'driver' => 'log',
            'channel' => 'log',
            'provider' => 'local_log',
            'status' => OtpDeliveryAttempt::STATUS_SENT,
        ]);

        $this->assertSame(1, OtpDeliveryAttempt::count());
    }

    public function test_delivery_attempt_row_never_contains_full_e164_or_otp_plaintext(): void
    {
        $phone = '+15558887702';

        $this->postJson('/api/mobile/auth/request-otp', ['phone' => $phone])->assertOk();

        $row = OtpDeliveryAttempt::firstOrFail();
        $blob = json_encode($row->getAttributes());

        $this->assertIsString($blob);
        $this->assertStringNotContainsString($phone, $blob);
        $this->assertSame(hash('sha256', $phone), $row->phone_hash);
        $this->assertMatchesRegularExpression('/^\*+\d{4}$/', $row->phone_masked);
    }

    public function test_twilio_sms_missing_configuration_records_failed_audit_row(): void
    {
        config([
            'eventaat-notifications.otp.driver' => 'twilio_sms',
            'eventaat-notifications.twilio.account_sid' => 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            'eventaat-notifications.twilio.auth_token' => 'token_val',
            'eventaat-notifications.twilio.messaging_service_sid' => '',
        ]);

        try {
            app(OtpSender::class)->send('+15558887703', '123456');
            $this->fail('Expected MissingTwilioOtpConfigurationException.');
        } catch (MissingTwilioOtpConfigurationException) {
            //
        }

        $this->assertDatabaseHas('otp_delivery_attempts', [
            'phone_hash' => hash('sha256', '+15558887703'),
            'driver' => 'twilio_sms',
            'channel' => 'sms',
            'provider' => 'twilio',
            'status' => OtpDeliveryAttempt::STATUS_FAILED,
            'error_code' => 'configuration',
        ]);
    }

    public function test_platform_super_admin_can_view_otp_delivery_attempts(): void
    {
        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        $this->actingAs($admin);

        $this->get('/platform/otp-delivery-attempts')->assertOk();
    }

    public function test_platform_operations_admin_can_view_otp_delivery_attempts(): void
    {
        $admin = User::where('email', 'operations_admin@eventaat.test')->firstOrFail();
        $this->actingAs($admin);

        $this->get('/platform/otp-delivery-attempts')->assertOk();
    }

    public function test_restaurant_owner_cannot_access_otp_delivery_attempts(): void
    {
        $user = User::where('email', 'restaurant_owner@eventaat.test')->firstOrFail();
        $this->actingAs($user);

        $this->get('/platform/otp-delivery-attempts')->assertForbidden();
    }

    public function test_customer_cannot_access_otp_delivery_attempts(): void
    {
        $user = User::where('email', 'customer@eventaat.test')->firstOrFail();
        $this->actingAs($user);

        $this->get('/platform/otp-delivery-attempts')->assertForbidden();
    }
}
