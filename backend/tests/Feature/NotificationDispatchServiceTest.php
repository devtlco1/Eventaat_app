<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\BranchStatus;
use App\Enums\RestaurantStatus;
use App\Models\Booking;
use App\Models\BookingNotification;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\Notifications\NotificationDispatchService;
use Database\Seeders\RolesAndTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NotificationDispatchServiceTest extends TestCase
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
            'phone' => '+15550001111',
            'email' => 'dispatch_customer@eventaat.test',
            'password' => Hash::make('x'),
        ]);
        $customer->syncRoles(['customer']);

        $restaurant = Restaurant::create([
            'name' => 'R',
            'slug' => 'r-dispatch',
            'status' => RestaurantStatus::Active,
        ]);

        $branch = Branch::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'B',
            'code' => 'b-dispatch',
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

    public function test_pending_notification_can_be_marked_sent(): void
    {
        $n = $this->makePendingInternalNotification();

        $ok = app(NotificationDispatchService::class)->markSent($n);
        $this->assertTrue($ok);

        $n->refresh();
        $this->assertSame('sent', $n->status);
        $this->assertNotNull($n->sent_at);
        $this->assertNull($n->failed_at);
        $this->assertNull($n->failure_reason);
    }

    public function test_pending_notification_can_be_marked_skipped(): void
    {
        $n = $this->makePendingInternalNotification();

        $ok = app(NotificationDispatchService::class)->markSkipped($n);
        $this->assertTrue($ok);

        $n->refresh();
        $this->assertSame('skipped', $n->status);
        $this->assertNull($n->sent_at);
        $this->assertNull($n->failed_at);
        $this->assertNull($n->failure_reason);
    }

    public function test_pending_notification_can_be_marked_failed_with_reason(): void
    {
        $n = $this->makePendingInternalNotification();

        $ok = app(NotificationDispatchService::class)->markFailed($n, 'Provider unavailable');
        $this->assertTrue($ok);

        $n->refresh();
        $this->assertSame('failed', $n->status);
        $this->assertNull($n->sent_at);
        $this->assertNotNull($n->failed_at);
        $this->assertSame('Provider unavailable', $n->failure_reason);
    }

    public function test_sent_notification_is_final_and_is_not_marked_again(): void
    {
        $n = $this->makePendingInternalNotification();
        $this->assertTrue(app(NotificationDispatchService::class)->markSent($n));

        $n->refresh();
        $sentAt = $n->sent_at;

        $this->assertFalse(app(NotificationDispatchService::class)->markSkipped($n));
        $this->assertFalse(app(NotificationDispatchService::class)->markFailed($n, 'x'));

        $n->refresh();
        $this->assertSame('sent', $n->status);
        $this->assertSame($sentAt?->toISOString(), $n->sent_at?->toISOString());
    }
}

