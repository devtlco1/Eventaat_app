<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\BranchStatus;
use App\Enums\RestaurantStatus;
use App\Models\Booking;
use App\Models\BookingNotification;
use App\Models\Branch;
use App\Models\NotificationDispatchAttempt;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\Notifications\NotificationDispatchService;
use App\Services\Notifications\Providers\NotificationProvider;
use App\Services\Notifications\Providers\TwilioSmsNotificationProvider;
use Database\Seeders\RolesAndTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class TwilioSmsBookingNotificationDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);

        config([
            'eventaat-notifications.booking_notifications.driver' => 'twilio_sms',
            'eventaat-notifications.twilio.account_sid' => 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            'eventaat-notifications.twilio.auth_token' => 'unit_test_auth_token',
            'eventaat-notifications.twilio.messaging_service_sid' => 'MGxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            'eventaat-notifications.twilio.notification_validity_period' => 36000,
        ]);
    }

    private function makePendingInternalNotification(?string $notificationRecipientPhone = '+15550002222', string $message = 'Your booking update.'): BookingNotification
    {
        $customer = User::create([
            'name' => 'C',
            'phone' => '+15550003338',
            'email' => 'twilio_booking_'.uniqid('', true).'@eventaat.test',
            'password' => Hash::make('x'),
        ]);
        $customer->syncRoles(['customer']);

        $restaurant = Restaurant::create([
            'name' => 'R',
            'slug' => 'r-twilio-booking',
            'status' => RestaurantStatus::Active,
        ]);

        $branch = Branch::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'B',
            'code' => 'b-twilio-booking',
            'status' => BranchStatus::Active,
        ]);

        $booking = Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'branch_id' => $branch->id,
            'starts_at' => Carbon::now()->addHours(4),
            'party_size' => 2,
            'status' => BookingStatus::Pending,
        ]);

        return BookingNotification::create([
            'booking_id' => $booking->id,
            'user_id' => $customer->id,
            'channel' => 'internal',
            'event' => BookingNotification::EVENT_BOOKING_CREATED,
            'recipient_phone' => $notificationRecipientPhone,
            'recipient_name' => $customer->name,
            'title' => 'T',
            'message' => $message,
            'status' => 'pending',
            'payload' => ['event' => BookingNotification::EVENT_BOOKING_CREATED],
        ]);
    }

    public function test_successful_twilio_send_records_attempt_with_message_sid(): void
    {
        $n = $this->makePendingInternalNotification();

        $messages = Mockery::mock();
        $messages->shouldReceive('create')
            ->once()
            ->withArgs(function (string $to, array $params): bool {
                return $to === '+15550002222'
                    && ($params['messagingServiceSid'] ?? '') === 'MGxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
                    && ($params['body'] ?? '') === 'Your booking update.'
                    && (int) ($params['validityPeriod'] ?? 0) === 36000;
            })
            ->andReturn((object) ['sid' => 'SM_booking_notify_01']);

        $client = Mockery::mock(Client::class);
        $client->messages = $messages;

        app()->instance(NotificationProvider::class, new TwilioSmsNotificationProvider($client));

        $this->assertTrue(app(NotificationDispatchService::class)->dispatchInternalDryRun($n));

        $n->refresh();
        $this->assertSame('sent', $n->status);

        $attempt = NotificationDispatchAttempt::where('booking_notification_id', $n->id)->firstOrFail();
        $this->assertSame(TwilioSmsNotificationProvider::PROVIDER_NAME, $attempt->provider);
        $this->assertSame('success', $attempt->status);
        $this->assertSame('SM_booking_notify_01', $attempt->provider_message_id);
    }

    public function test_twilio_api_failure_records_failed_attempt_without_swallowing(): void
    {
        $n = $this->makePendingInternalNotification();

        $messages = Mockery::mock();
        $messages->shouldReceive('create')
            ->once()
            ->andThrow(new TwilioException('simulated_twilio_booking_failure', 30007));

        $client = Mockery::mock(Client::class);
        $client->messages = $messages;

        app()->instance(NotificationProvider::class, new TwilioSmsNotificationProvider($client));

        $this->assertTrue(app(NotificationDispatchService::class)->dispatchInternalDryRun($n));

        $n->refresh();
        $this->assertSame('failed', $n->status);
        $this->assertStringContainsString('30007', (string) $n->failure_reason);

        $this->assertDatabaseHas('notification_dispatch_attempts', [
            'booking_notification_id' => $n->id,
            'provider' => TwilioSmsNotificationProvider::PROVIDER_NAME,
            'status' => 'failed',
        ]);
    }

    public function test_missing_twilio_config_marks_notification_failed_with_clear_reason(): void
    {
        config([
            'eventaat-notifications.twilio.account_sid' => '',
            'eventaat-notifications.twilio.auth_token' => '',
            'eventaat-notifications.twilio.messaging_service_sid' => '',
        ]);

        $n = $this->makePendingInternalNotification();

        app()->instance(NotificationProvider::class, new TwilioSmsNotificationProvider);

        $this->assertTrue(app(NotificationDispatchService::class)->dispatchInternalDryRun($n));

        $n->refresh();
        $this->assertSame('failed', $n->status);
        $this->assertStringContainsString('TWILIO_ACCOUNT_SID', (string) $n->failure_reason);

        $attempt = NotificationDispatchAttempt::where('booking_notification_id', $n->id)->firstOrFail();
        $this->assertSame('failed', $attempt->status);
    }

    public function test_missing_recipient_phone_fails_dispatch_with_safe_reason(): void
    {
        $n = $this->makePendingInternalNotification(null);

        $messages = Mockery::mock();
        $messages->shouldReceive('create')->never();

        $client = Mockery::mock(Client::class);
        $client->messages = $messages;

        app()->instance(NotificationProvider::class, new TwilioSmsNotificationProvider($client));

        $this->assertTrue(app(NotificationDispatchService::class)->dispatchInternalDryRun($n));

        $n->refresh();
        $this->assertSame('failed', $n->status);
        $this->assertStringContainsString('No recipient phone', (string) $n->failure_reason);
    }
}
