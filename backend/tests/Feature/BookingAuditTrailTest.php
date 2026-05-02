<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\BranchStatus;
use App\Enums\RestaurantStatus;
use App\Models\Booking;
use App\Models\BookingAuditLog;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\Bookings\BookingTransitionException;
use App\Services\Bookings\BookingTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BookingAuditTrailTest extends TestCase
{
    use RefreshDatabase;

    private function makeBooking(array $overrides = []): Booking
    {
        static $seq = 1;

        $customer = User::create([
            'name' => 'Audit Customer',
            'phone' => '+15550777'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
            'email' => "audit_customer_{$seq}@eventaat.test",
            'password' => Hash::make('x'),
        ]);

        $restaurant = Restaurant::create([
            'name' => 'Audit Restaurant',
            'slug' => "audit-r-{$seq}",
            'status' => RestaurantStatus::Active,
        ]);

        $branch = Branch::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Audit Branch',
            'code' => "ab{$seq}",
            'status' => BranchStatus::Active,
        ]);

        $seq++;

        return Booking::create(array_merge([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'branch_id' => $branch->id,
            'starts_at' => Carbon::now()->addHours(3),
            'party_size' => 2,
            'status' => BookingStatus::Pending,
        ], $overrides));
    }

    public function test_accept_reject_cancel_write_audit_rows_with_status_columns(): void
    {
        $svc = app(BookingTransitionService::class);

        $b1 = $this->makeBooking(['status' => BookingStatus::Pending]);
        $svc->accept($b1);
        $this->assertDatabaseHas('booking_audit_logs', [
            'booking_id' => $b1->id,
            'action' => BookingAuditLog::ACTION_ACCEPTED,
            'from_status' => 'pending',
            'to_status' => 'accepted',
        ]);

        $b2 = $this->makeBooking(['status' => BookingStatus::Pending]);
        $svc->reject($b2);
        $this->assertDatabaseHas('booking_audit_logs', [
            'booking_id' => $b2->id,
            'action' => BookingAuditLog::ACTION_REJECTED,
            'from_status' => 'pending',
            'to_status' => 'rejected',
        ]);

        $b3 = $this->makeBooking(['status' => BookingStatus::Pending]);
        $svc->cancel($b3);
        $this->assertDatabaseHas('booking_audit_logs', [
            'booking_id' => $b3->id,
            'action' => BookingAuditLog::ACTION_CANCELLED,
            'from_status' => 'pending',
            'to_status' => 'cancelled',
        ]);
    }

    public function test_day_of_transitions_write_audit_rows(): void
    {
        $svc = app(BookingTransitionService::class);
        $booking = $this->makeBooking(['status' => BookingStatus::Pending]);

        $svc->accept($booking);
        $svc->arrive($booking->fresh());
        $svc->seat($booking->fresh());
        $svc->complete($booking->fresh());

        $actions = BookingAuditLog::query()
            ->where('booking_id', $booking->id)
            ->orderBy('id')
            ->pluck('action')
            ->all();

        $this->assertSame([
            BookingAuditLog::ACTION_ACCEPTED,
            BookingAuditLog::ACTION_ARRIVED,
            BookingAuditLog::ACTION_SEATED,
            BookingAuditLog::ACTION_COMPLETED,
        ], $actions);
    }

    public function test_no_show_transition_writes_audit_row(): void
    {
        $svc = app(BookingTransitionService::class);
        $booking = $this->makeBooking(['status' => BookingStatus::Pending]);

        $svc->accept($booking);
        $svc->noShow($booking->fresh());

        $this->assertDatabaseHas('booking_audit_logs', [
            'booking_id' => $booking->id,
            'action' => BookingAuditLog::ACTION_NO_SHOW,
            'from_status' => 'accepted',
            'to_status' => 'no_show',
        ]);
    }

    public function test_invalid_transition_does_not_create_audit_log(): void
    {
        $booking = $this->makeBooking([
            'status' => BookingStatus::Accepted,
            'accepted_at' => Carbon::now(),
        ]);

        $before = BookingAuditLog::query()->count();

        try {
            app(BookingTransitionService::class)->reject($booking);
        } catch (BookingTransitionException $e) {
            // expected
        }

        $this->assertSame($before, BookingAuditLog::query()->count());
    }

    public function test_actor_id_recorded_when_web_guard_authenticated(): void
    {
        $actor = User::create([
            'name' => 'Staff Auditor',
            'phone' => '+15550601001',
            'email' => 'staff_auditor@eventaat.test',
            'password' => Hash::make('x'),
        ]);

        $this->actingAs($actor, 'web');

        $booking = $this->makeBooking(['status' => BookingStatus::Pending]);
        app(BookingTransitionService::class)->accept($booking);

        $this->assertDatabaseHas('booking_audit_logs', [
            'booking_id' => $booking->id,
            'actor_id' => $actor->id,
            'actor_type' => User::class,
            'action' => BookingAuditLog::ACTION_ACCEPTED,
        ]);
    }

    public function test_actor_id_recorded_when_sanctum_guard_authenticated(): void
    {
        $customer = User::create([
            'name' => 'Mobile Canceller',
            'phone' => '+15550602002',
            'email' => 'mobile_audit_cancel@eventaat.test',
            'password' => Hash::make('x'),
        ]);

        $booking = $this->makeBooking([
            'customer_id' => $customer->id,
            'status' => BookingStatus::Accepted,
            'accepted_at' => Carbon::now(),
        ]);

        $this->actingAs($customer, 'sanctum');

        app(BookingTransitionService::class)->cancel($booking);

        $this->assertDatabaseHas('booking_audit_logs', [
            'booking_id' => $booking->id,
            'actor_id' => $customer->id,
            'action' => BookingAuditLog::ACTION_CANCELLED,
        ]);
    }
}
