<?php

namespace Tests\Feature;

use App\Enums\RestaurantStaffRole;
use App\Enums\RestaurantStatus;
use App\Filament\Platform\Resources\SupportTickets\Pages\ListSupportTickets as PlatformListSupportTickets;
use App\Filament\Platform\Resources\SupportTickets\Pages\ViewSupportTicket as PlatformViewSupportTicket;
use App\Filament\Platform\Resources\SupportTickets\RelationManagers\SupportTicketActivitiesRelationManager;
use App\Filament\Restaurant\Resources\SupportTickets\SupportTicketResource as RestaurantSupportTicketResource;
use App\Models\Restaurant;
use App\Models\RestaurantStaffAssignment;
use App\Models\SupportTicket;
use App\Models\SupportTicketActivity;
use App\Models\User;
use Database\Seeders\RolesAndTestUsersSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupportTicketActivitiesDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
    }

    /**
     * @return array{restaurant: Restaurant, ticket: SupportTicket}
     */
    private function seedRestaurantAndTicket(): array
    {
        $restaurant = Restaurant::create([
            'name' => 'Restaurant A',
            'slug' => 'restaurant-a',
            'status' => RestaurantStatus::Active,
        ]);

        $ticket = SupportTicket::create([
            'restaurant_id' => $restaurant->id,
            'subject' => 'Activity ticket',
            'category' => SupportTicket::CATEGORY_GENERAL,
            'priority' => SupportTicket::PRIORITY_NORMAL,
            'status' => SupportTicket::STATUS_OPEN,
            'source' => SupportTicket::SOURCE_DASHBOARD,
        ]);

        return compact('restaurant', 'ticket');
    }

    public function test_platform_can_add_internal_note_via_relation_manager(): void
    {
        $ticket = $this->seedRestaurantAndTicket()['ticket'];

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(SupportTicketActivitiesRelationManager::class, [
            'ownerRecord' => $ticket,
            'pageClass' => PlatformViewSupportTicket::class,
        ])
            ->call('mountAction', 'create', [], ['table' => true])
            ->fillForm(['message' => 'Escalated to ops'])
            ->call('callMountedAction');

        $this->assertDatabaseHas('support_ticket_activities', [
            'support_ticket_id' => $ticket->id,
            'type' => SupportTicketActivity::TYPE_NOTE,
            'message' => 'Escalated to ops',
            'user_id' => $admin->id,
        ]);
    }

    public function test_status_workflow_records_status_change_activities_and_resolve_maps_old_new(): void
    {
        $ticket = $this->seedRestaurantAndTicket()['ticket'];

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformListSupportTickets::class)
            ->callTableAction('mark_in_progress', $ticket);

        $this->assertDatabaseHas('support_ticket_activities', [
            'support_ticket_id' => $ticket->id,
            'type' => SupportTicketActivity::TYPE_STATUS_CHANGE,
            'old_status' => SupportTicket::STATUS_OPEN,
            'new_status' => SupportTicket::STATUS_IN_PROGRESS,
            'user_id' => $admin->id,
        ]);

        Livewire::test(PlatformListSupportTickets::class)
            ->callTableAction('resolve', $ticket);

        $this->assertDatabaseHas('support_ticket_activities', [
            'support_ticket_id' => $ticket->id,
            'type' => SupportTicketActivity::TYPE_STATUS_CHANGE,
            'old_status' => SupportTicket::STATUS_IN_PROGRESS,
            'new_status' => SupportTicket::STATUS_RESOLVED,
        ]);
    }

    public function test_reopen_logs_status_change_and_preserves_resolved_and_closed_timestamps(): void
    {
        $ticket = $this->seedRestaurantAndTicket()['ticket'];

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformListSupportTickets::class)->callTableAction('resolve', $ticket);
        $ticket->refresh();
        $resolvedAt = $ticket->resolved_at;
        $this->assertNotNull($resolvedAt);

        Livewire::test(PlatformListSupportTickets::class)->callTableAction('close', $ticket);
        $ticket->refresh();
        $closedAt = $ticket->closed_at;
        $this->assertNotNull($closedAt);

        Livewire::test(PlatformListSupportTickets::class)->callTableAction('reopen', $ticket);
        $ticket->refresh();

        $this->assertSame(SupportTicket::STATUS_OPEN, $ticket->status);
        $this->assertTrue($ticket->resolved_at->equalTo($resolvedAt));
        $this->assertTrue($ticket->closed_at->equalTo($closedAt));

        $this->assertDatabaseHas('support_ticket_activities', [
            'support_ticket_id' => $ticket->id,
            'type' => SupportTicketActivity::TYPE_STATUS_CHANGE,
            'old_status' => SupportTicket::STATUS_CLOSED,
            'new_status' => SupportTicket::STATUS_OPEN,
        ]);
    }

    public function test_restaurant_ticket_view_does_not_show_internal_activity_timeline(): void
    {
        $data = $this->seedRestaurantAndTicket();

        $owner = User::where('email', 'restaurant_owner@eventaat.test')->firstOrFail();
        RestaurantStaffAssignment::create([
            'user_id' => $owner->id,
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => null,
            'role' => RestaurantStaffRole::RestaurantOwner,
            'status' => 'active',
        ]);

        Filament::setCurrentPanel('restaurant');
        $this->actingAs($owner);

        $response = $this->get('/restaurant/support-tickets/'.$data['ticket']->getRouteKey());
        $response->assertOk();
        $response->assertDontSee('Internal activity');
        $response->assertDontSee('Add internal note');
    }

    public function test_restaurant_support_ticket_resource_has_no_activity_relation_manager(): void
    {
        $this->assertSame([], RestaurantSupportTicketResource::getRelations());
    }

    public function test_out_of_scope_restaurant_ticket_remains_denied(): void
    {
        $data = $this->seedRestaurantAndTicket();

        $ticketB = SupportTicket::create([
            'restaurant_id' => Restaurant::create([
                'name' => 'Restaurant B',
                'slug' => 'restaurant-b',
                'status' => RestaurantStatus::Active,
            ])->id,
            'subject' => 'Other restaurant ticket',
            'category' => SupportTicket::CATEGORY_GENERAL,
            'priority' => SupportTicket::PRIORITY_NORMAL,
            'status' => SupportTicket::STATUS_OPEN,
            'source' => SupportTicket::SOURCE_DASHBOARD,
        ]);

        $owner = User::where('email', 'restaurant_owner@eventaat.test')->firstOrFail();
        RestaurantStaffAssignment::create([
            'user_id' => $owner->id,
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => null,
            'role' => RestaurantStaffRole::RestaurantOwner,
            'status' => 'active',
        ]);

        Filament::setCurrentPanel('restaurant');
        $this->actingAs($owner);

        $status = $this->get('/restaurant/support-tickets/'.$ticketB->getRouteKey())->getStatusCode();
        $this->assertTrue(in_array($status, [403, 404], true));
    }
}
