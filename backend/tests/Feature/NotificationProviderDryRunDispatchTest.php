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
use App\Services\Notifications\Providers\InternalDryRunNotificationProvider;
use App\Services\Notifications\Providers\NotificationProviderResult;
use Database\Seeders\RolesAndTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NotificationProviderDryRunDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
    }

    private function makePendingInternalNotification(): BookingNotification
    {
        $customer = User::create([
            'name' => 'C',
            'phone' => '+15550002222',
            'email' => 'dryrun_customer@eventaat.test',
            'password' => Hash::make('x'),
        ]);
        $customer->syncRoles(['customer']);

        $restaurant = Restaurant::create([
            'name' => 'R',
            'slug' => 'r-dryrun',
            'status' => RestaurantStatus::Active,
        ]);

        $branch = Branch::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'B',
            'code' => 'b-dryrun',
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
            'recipient_phone' => $customer->phone,
            'recipient_name' => $customer->name,
            'title' => 'T',
            'message' => 'M',
            'status' => 'pending',
            'payload' => ['event' => BookingNotification::EVENT_BOOKING_CREATED],
        ]);
    }

    public function test_dry_run_dispatch_creates_attempt_row_and_marks_sent(): void
    {
        $n = $this->makePendingInternalNotification();

        $ok = app(NotificationDispatchService::class)->dispatchInternalDryRun($n);
        $this->assertTrue($ok);

        $n->refresh();
        $this->assertSame('sent', $n->status);

        $this->assertDatabaseHas('notification_dispatch_attempts', [
            'booking_notification_id' => $n->id,
            'provider' => InternalDryRunNotificationProvider::PROVIDER_NAME,
            'channel' => 'internal',
            'status' => 'success',
        ]);

        $attempt = NotificationDispatchAttempt::where('booking_notification_id', $n->id)->firstOrFail();
        $this->assertNotNull($attempt->attempted_at);
    }

    public function test_provider_failure_marks_notification_failed_and_stores_attempt(): void
    {
        $n = $this->makePendingInternalNotification();

        $fake = new class extends InternalDryRunNotificationProvider {
            public function send(BookingNotification $notification): NotificationProviderResult
            {
                return NotificationProviderResult::failure('Dry-run forced failure');
            }
        };

        app()->instance(InternalDryRunNotificationProvider::class, $fake);

        $ok = app(NotificationDispatchService::class)->dispatchInternalDryRun($n);
        $this->assertTrue($ok);

        $n->refresh();
        $this->assertSame('failed', $n->status);
        $this->assertSame('Dry-run forced failure', $n->failure_reason);

        $this->assertDatabaseHas('notification_dispatch_attempts', [
            'booking_notification_id' => $n->id,
            'status' => 'failed',
            'failure_reason' => 'Dry-run forced failure',
        ]);
    }

    public function test_non_pending_notification_cannot_be_dry_run_dispatched_and_creates_no_attempt(): void
    {
        $n = $this->makePendingInternalNotification();
        $n->update(['status' => 'sent']);

        $ok = app(NotificationDispatchService::class)->dispatchInternalDryRun($n);
        $this->assertFalse($ok);

        $this->assertSame(0, NotificationDispatchAttempt::count());
    }
}

