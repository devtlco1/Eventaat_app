<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\BranchStatus;
use App\Enums\CallCenterCallDirection;
use App\Enums\CallCenterCallOutcome;
use App\Enums\CallCenterCallReason;
use App\Enums\RestaurantStatus;
use App\Filament\Platform\Resources\CallCenterCalls\Pages\CreateCallCenterCall as PlatformCreateCallCenterCall;
use App\Filament\Platform\Resources\CallCenterCalls\Pages\EditCallCenterCall as PlatformEditCallCenterCall;
use App\Filament\Platform\Resources\CallCenterCalls\Pages\ListCallCenterCalls as PlatformListCallCenterCalls;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\CallCenterCall;
use App\Models\Restaurant;
use App\Models\User;
use Database\Seeders\RolesAndTestUsersSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class CallCenterCallFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
    }

    public function test_platform_roles_can_access_call_logs_restaurant_staff_cannot(): void
    {
        Filament::setCurrentPanel('platform');

        foreach (['super_admin@eventaat.test', 'operations_admin@eventaat.test'] as $email) {
            $this->flushSession();
            $user = User::where('email', $email)->firstOrFail();
            $this->actingAs($user);
            $this->get('/platform/call-center-calls')->assertOk();
        }

        $this->flushSession();
        $owner = User::where('email', 'restaurant_owner@eventaat.test')->firstOrFail();
        $this->actingAs($owner);

        $this->get('/platform/call-center-calls')->assertForbidden();
    }

    public function test_operations_admin_can_create_call_log_via_filament_defaults_handled_by(): void
    {
        $ops = User::where('email', 'operations_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($ops);

        Livewire::test(PlatformCreateCallCenterCall::class)
            ->set('data.direction', CallCenterCallDirection::Inbound->value)
            ->set('data.reason', CallCenterCallReason::General->value)
            ->set('data.outcome', CallCenterCallOutcome::Pending->value)
            ->set('data.phone', '+964770000001')
            ->set('data.handled_by_user_id', null)
            ->call('create')
            ->assertHasNoErrors();

        $call = CallCenterCall::query()->firstOrFail();
        $this->assertSame(CallCenterCallDirection::Inbound, $call->direction);
        $this->assertSame($ops->id, $call->handled_by_user_id);
    }

    public function test_operations_admin_can_edit_call_log_via_filament(): void
    {
        $call = CallCenterCall::create([
            'direction' => CallCenterCallDirection::Outbound,
            'reason' => CallCenterCallReason::Billing,
            'outcome' => CallCenterCallOutcome::Reached,
            'phone' => '+964770000002',
            'notes' => 'Original',
        ]);

        $ops = User::where('email', 'operations_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($ops);

        Livewire::test(PlatformEditCallCenterCall::class, ['record' => $call->getRouteKey()])
            ->set('data.notes', 'Updated note')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Updated note', $call->fresh()->notes);
    }

    public function test_mark_resolved_table_action_sets_outcome_and_completed_at(): void
    {
        $call = CallCenterCall::create([
            'direction' => CallCenterCallDirection::Inbound,
            'reason' => CallCenterCallReason::Complaint,
            'outcome' => CallCenterCallOutcome::Pending,
        ]);

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformListCallCenterCalls::class)
            ->callTableAction('mark_resolved', $call);

        $call->refresh();
        $this->assertSame(CallCenterCallOutcome::Resolved, $call->outcome);
        $this->assertNotNull($call->completed_at);
    }

    public function test_completed_at_with_pending_outcome_normalizes_to_resolved_on_save(): void
    {
        $call = new CallCenterCall([
            'direction' => CallCenterCallDirection::Inbound,
            'reason' => CallCenterCallReason::General,
            'outcome' => CallCenterCallOutcome::Pending,
            'completed_at' => Carbon::now(),
        ]);

        $call->save();

        $this->assertSame(CallCenterCallOutcome::Resolved, $call->fresh()->outcome);
    }

    public function test_booking_must_match_restaurant_when_both_set(): void
    {
        $r1 = Restaurant::create([
            'name' => 'Call Center R1',
            'slug' => 'call-center-r1',
            'status' => RestaurantStatus::Active,
        ]);

        $r2 = Restaurant::create([
            'name' => 'Call Center R2',
            'slug' => 'call-center-r2',
            'status' => RestaurantStatus::Active,
        ]);

        $branch = Branch::create([
            'restaurant_id' => $r2->id,
            'name' => 'Main',
            'code' => 'main',
            'status' => BranchStatus::Active,
        ]);

        $customer = User::where('email', 'customer@eventaat.test')->firstOrFail();

        $booking = Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $r2->id,
            'branch_id' => $branch->id,
            'starts_at' => Carbon::now()->addDay(),
            'party_size' => 2,
            'status' => BookingStatus::Pending,
        ]);

        $this->expectException(ValidationException::class);

        CallCenterCall::create([
            'restaurant_id' => $r1->id,
            'booking_id' => $booking->id,
            'direction' => CallCenterCallDirection::Inbound,
            'reason' => CallCenterCallReason::BookingFollowUp,
            'outcome' => CallCenterCallOutcome::Pending,
        ]);
    }
}
