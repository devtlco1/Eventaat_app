<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\BranchStatus;
use App\Enums\RestaurantStaffRole;
use App\Enums\RestaurantStatus;
use App\Filament\Platform\Resources\SupportTickets\Pages\CreateSupportTicket as PlatformCreateSupportTicket;
use App\Filament\Platform\Resources\SupportTickets\Pages\ListSupportTickets as PlatformListSupportTickets;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantStaffAssignment;
use App\Models\SupportTicket;
use App\Models\User;
use Database\Seeders\RolesAndTestUsersSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class SupportTicketsDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
    }

    /**
     * @return array{a: Restaurant, b: Restaurant, aBranch: Branch, bBranch: Branch, bookingA: Booking, aWide: SupportTicket, aBranchTicket: SupportTicket, bBranchTicket: SupportTicket, platformTicket: SupportTicket}
     */
    private function seedRestaurantsAndTickets(): array
    {
        $a = Restaurant::create([
            'name' => 'Restaurant A',
            'slug' => 'restaurant-a',
            'status' => RestaurantStatus::Active,
        ]);

        $b = Restaurant::create([
            'name' => 'Restaurant B',
            'slug' => 'restaurant-b',
            'status' => RestaurantStatus::Active,
        ]);

        $aBranch = Branch::create([
            'restaurant_id' => $a->id,
            'name' => 'A Main',
            'code' => 'main',
            'status' => BranchStatus::Active,
        ]);

        $bBranch = Branch::create([
            'restaurant_id' => $b->id,
            'name' => 'B Main',
            'code' => 'main',
            'status' => BranchStatus::Active,
        ]);

        $customer = User::where('email', 'customer@eventaat.test')->firstOrFail();

        $bookingA = Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $a->id,
            'branch_id' => $aBranch->id,
            'starts_at' => Carbon::now()->addDay(),
            'party_size' => 2,
            'status' => BookingStatus::Completed,
        ]);

        $aWide = SupportTicket::create([
            'restaurant_id' => $a->id,
            'branch_id' => null,
            'booking_id' => null,
            'subject' => 'Wide ticket A',
            'category' => SupportTicket::CATEGORY_GENERAL,
            'priority' => SupportTicket::PRIORITY_NORMAL,
            'status' => SupportTicket::STATUS_OPEN,
            'source' => SupportTicket::SOURCE_DASHBOARD,
            'message' => 'Restaurant-wide concern',
        ]);

        $aBranchTicket = SupportTicket::create([
            'restaurant_id' => $a->id,
            'branch_id' => $aBranch->id,
            'booking_id' => null,
            'subject' => 'Branch ticket A',
            'category' => SupportTicket::CATEGORY_RESTAURANT,
            'priority' => SupportTicket::PRIORITY_HIGH,
            'status' => SupportTicket::STATUS_OPEN,
            'source' => SupportTicket::SOURCE_PHONE,
            'message' => 'Branch issue',
        ]);

        $bBranchTicket = SupportTicket::create([
            'restaurant_id' => $b->id,
            'branch_id' => $bBranch->id,
            'booking_id' => null,
            'subject' => 'Branch ticket B',
            'category' => SupportTicket::CATEGORY_APP,
            'priority' => SupportTicket::PRIORITY_LOW,
            'status' => SupportTicket::STATUS_OPEN,
            'source' => SupportTicket::SOURCE_DASHBOARD,
            'message' => 'B branch',
        ]);

        $platformTicket = SupportTicket::create([
            'restaurant_id' => null,
            'branch_id' => null,
            'booking_id' => null,
            'subject' => 'Platform-only ticket',
            'category' => SupportTicket::CATEGORY_OTHER,
            'priority' => SupportTicket::PRIORITY_NORMAL,
            'status' => SupportTicket::STATUS_OPEN,
            'source' => SupportTicket::SOURCE_DASHBOARD,
            'message' => 'No restaurant',
        ]);

        return compact(
            'a',
            'b',
            'aBranch',
            'bBranch',
            'bookingA',
            'aWide',
            'aBranchTicket',
            'bBranchTicket',
            'platformTicket'
        );
    }

    private function assertDeniedOrNotFound(int $status): void
    {
        $this->assertTrue(in_array($status, [403, 404], true), "Expected 403/404, got {$status}");
    }

    public function test_platform_can_list_and_create_tickets(): void
    {
        $data = $this->seedRestaurantsAndTickets();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        $index = $this->get('/platform/support-tickets');
        $index->assertOk();
        $index->assertSee($data['aWide']->subject);
        $index->assertSee($data['platformTicket']->subject);
        $index->assertSee('/platform/support-tickets/create');

        Livewire::test(PlatformCreateSupportTicket::class)
            ->set('data.subject', 'New ticket')
            ->set('data.category', SupportTicket::CATEGORY_GENERAL)
            ->set('data.priority', SupportTicket::PRIORITY_NORMAL)
            ->set('data.status', SupportTicket::STATUS_OPEN)
            ->set('data.source', SupportTicket::SOURCE_DASHBOARD)
            ->set('data.restaurant_id', null)
            ->set('data.branch_id', null)
            ->set('data.booking_id', null)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('support_tickets', [
            'subject' => 'New ticket',
            'restaurant_id' => null,
            'branch_id' => null,
            'booking_id' => null,
        ]);
    }

    public function test_platform_resolve_close_reopen_workflow_and_timestamps(): void
    {
        $data = $this->seedRestaurantsAndTickets();

        $ticket = SupportTicket::create([
            'restaurant_id' => $data['a']->id,
            'branch_id' => $data['aBranch']->id,
            'subject' => 'Workflow ticket',
            'category' => SupportTicket::CATEGORY_BOOKING,
            'priority' => SupportTicket::PRIORITY_NORMAL,
            'status' => SupportTicket::STATUS_OPEN,
            'source' => SupportTicket::SOURCE_DASHBOARD,
        ]);

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformListSupportTickets::class)
            ->callTableAction('mark_in_progress', $ticket);

        $ticket->refresh();
        $this->assertSame(SupportTicket::STATUS_IN_PROGRESS, $ticket->status);

        Livewire::test(PlatformListSupportTickets::class)
            ->callTableAction('resolve', $ticket);

        $ticket->refresh();
        $this->assertSame(SupportTicket::STATUS_RESOLVED, $ticket->status);
        $this->assertNotNull($ticket->resolved_at);

        $resolvedAt = $ticket->resolved_at;

        Livewire::test(PlatformListSupportTickets::class)
            ->callTableAction('close', $ticket);

        $ticket->refresh();
        $this->assertSame(SupportTicket::STATUS_CLOSED, $ticket->status);
        $this->assertNotNull($ticket->closed_at);

        Livewire::test(PlatformListSupportTickets::class)
            ->callTableAction('reopen', $ticket);

        $ticket->refresh();
        $this->assertSame(SupportTicket::STATUS_OPEN, $ticket->status);
        $this->assertTrue($ticket->resolved_at->equalTo($resolvedAt));
        $this->assertNotNull($ticket->closed_at);
    }

    public function test_restaurant_owner_sees_restaurant_wide_and_branch_tickets(): void
    {
        $data = $this->seedRestaurantsAndTickets();

        $owner = User::where('email', 'restaurant_owner@eventaat.test')->firstOrFail();
        RestaurantStaffAssignment::create([
            'user_id' => $owner->id,
            'restaurant_id' => $data['a']->id,
            'branch_id' => null,
            'role' => RestaurantStaffRole::RestaurantOwner,
            'status' => 'active',
        ]);

        Filament::setCurrentPanel('restaurant');
        $this->actingAs($owner);

        $index = $this->get('/restaurant/support-tickets');
        $index->assertOk();
        $index->assertSee($data['aWide']->subject);
        $index->assertSee($data['aBranchTicket']->subject);
        $index->assertDontSee($data['bBranchTicket']->subject);
        $index->assertDontSee($data['platformTicket']->subject);

        $this->get("/restaurant/support-tickets/{$data['bBranchTicket']->id}")
            ->tap(fn ($resp) => $this->assertDeniedOrNotFound($resp->getStatusCode()));
    }

    public function test_branch_roles_see_branch_scoped_tickets_only(): void
    {
        $data = $this->seedRestaurantsAndTickets();

        $manager = User::where('email', 'branch_manager@eventaat.test')->firstOrFail();
        RestaurantStaffAssignment::create([
            'user_id' => $manager->id,
            'restaurant_id' => $data['a']->id,
            'branch_id' => $data['aBranch']->id,
            'role' => RestaurantStaffRole::BranchManager,
            'status' => 'active',
        ]);

        Filament::setCurrentPanel('restaurant');
        $this->actingAs($manager);

        $index = $this->get('/restaurant/support-tickets');
        $index->assertOk();
        $index->assertSee($data['aBranchTicket']->subject);
        $index->assertDontSee($data['aWide']->subject);

        $this->get("/restaurant/support-tickets/{$data['aWide']->id}")
            ->tap(fn ($resp) => $this->assertDeniedOrNotFound($resp->getStatusCode()));
    }

    public function test_restaurant_panel_has_no_edit_route(): void
    {
        $data = $this->seedRestaurantsAndTickets();

        $owner = User::where('email', 'restaurant_owner@eventaat.test')->firstOrFail();
        RestaurantStaffAssignment::create([
            'user_id' => $owner->id,
            'restaurant_id' => $data['a']->id,
            'branch_id' => null,
            'role' => RestaurantStaffRole::RestaurantOwner,
            'status' => 'active',
        ]);

        Filament::setCurrentPanel('restaurant');
        $this->actingAs($owner);

        $this->get('/restaurant/support-tickets/create')->assertNotFound();
        $this->get("/restaurant/support-tickets/{$data['aWide']->id}/edit")->assertNotFound();
    }

    public function test_branch_must_belong_to_restaurant_on_create(): void
    {
        $data = $this->seedRestaurantsAndTickets();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformCreateSupportTicket::class)
            ->set('data.subject', 'Bad branch')
            ->set('data.category', SupportTicket::CATEGORY_GENERAL)
            ->set('data.priority', SupportTicket::PRIORITY_NORMAL)
            ->set('data.status', SupportTicket::STATUS_OPEN)
            ->set('data.source', SupportTicket::SOURCE_DASHBOARD)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', $data['bBranch']->id)
            ->call('create')
            ->assertHasErrors(['data.branch_id']);
    }

    public function test_booking_must_belong_to_restaurant_and_branch(): void
    {
        $data = $this->seedRestaurantsAndTickets();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformCreateSupportTicket::class)
            ->set('data.subject', 'Bad booking')
            ->set('data.category', SupportTicket::CATEGORY_BOOKING)
            ->set('data.priority', SupportTicket::PRIORITY_NORMAL)
            ->set('data.status', SupportTicket::STATUS_OPEN)
            ->set('data.source', SupportTicket::SOURCE_DASHBOARD)
            ->set('data.restaurant_id', $data['b']->id)
            ->set('data.branch_id', $data['bBranch']->id)
            ->set('data.booking_id', $data['bookingA']->id)
            ->call('create')
            ->assertHasErrors(['data.booking_id']);
    }

    public function test_invalid_category_rejected_at_model(): void
    {
        $data = $this->seedRestaurantsAndTickets();

        $this->expectException(ValidationException::class);

        SupportTicket::create([
            'restaurant_id' => $data['a']->id,
            'subject' => 'Bad cat',
            'category' => 'not-a-category',
            'priority' => SupportTicket::PRIORITY_NORMAL,
            'status' => SupportTicket::STATUS_OPEN,
            'source' => SupportTicket::SOURCE_DASHBOARD,
        ]);
    }

    public function test_resolved_and_closed_status_sets_timestamps_when_blank(): void
    {
        $data = $this->seedRestaurantsAndTickets();

        $resolved = SupportTicket::create([
            'restaurant_id' => $data['a']->id,
            'subject' => 'Resolved ts',
            'category' => SupportTicket::CATEGORY_GENERAL,
            'priority' => SupportTicket::PRIORITY_NORMAL,
            'status' => SupportTicket::STATUS_RESOLVED,
            'source' => SupportTicket::SOURCE_DASHBOARD,
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        $this->assertNotNull($resolved->fresh()->resolved_at);

        $closed = SupportTicket::create([
            'restaurant_id' => $data['a']->id,
            'subject' => 'Closed ts',
            'category' => SupportTicket::CATEGORY_GENERAL,
            'priority' => SupportTicket::PRIORITY_NORMAL,
            'status' => SupportTicket::STATUS_CLOSED,
            'source' => SupportTicket::SOURCE_DASHBOARD,
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        $this->assertNotNull($closed->fresh()->closed_at);
    }

    public function test_booking_prefills_user_and_customer_fields(): void
    {
        $data = $this->seedRestaurantsAndTickets();

        $ticket = SupportTicket::create([
            'restaurant_id' => $data['a']->id,
            'branch_id' => $data['aBranch']->id,
            'booking_id' => $data['bookingA']->id,
            'subject' => 'Booking linked',
            'category' => SupportTicket::CATEGORY_BOOKING,
            'priority' => SupportTicket::PRIORITY_NORMAL,
            'status' => SupportTicket::STATUS_OPEN,
            'source' => SupportTicket::SOURCE_DASHBOARD,
        ]);

        $ticket->refresh();
        $this->assertSame((int) $data['bookingA']->customer_id, (int) $ticket->user_id);
        $this->assertNotNull($ticket->customer_name);
    }

    public function test_platform_ticket_disallows_branch_without_restaurant(): void
    {
        $data = $this->seedRestaurantsAndTickets();

        $this->expectException(ValidationException::class);

        SupportTicket::create([
            'restaurant_id' => null,
            'branch_id' => $data['aBranch']->id,
            'subject' => 'Invalid',
            'category' => SupportTicket::CATEGORY_GENERAL,
            'priority' => SupportTicket::PRIORITY_NORMAL,
            'status' => SupportTicket::STATUS_OPEN,
            'source' => SupportTicket::SOURCE_DASHBOARD,
        ]);
    }
}
