<?php

namespace Tests\Feature;

use App\Enums\BranchStatus;
use App\Enums\RestaurantStaffRole;
use App\Enums\RestaurantStatus;
use App\Enums\TableStatus;
use App\Filament\Platform\Resources\Bookings\Pages\CreateBooking as PlatformCreateBooking;
use App\Filament\Restaurant\Resources\Bookings\Pages\CreateBooking as RestaurantCreateBooking;
use App\Models\Branch;
use App\Models\BranchAvailabilityRule;
use App\Models\Restaurant;
use App\Models\RestaurantEvent;
use App\Models\RestaurantStaffAssignment;
use App\Models\RestaurantTable;
use App\Models\SeatingArea;
use App\Models\User;
use Database\Seeders\RolesAndTestUsersSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ManualBookingFilamentCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createAvailabilityRule(Branch $branch, array $overrides = []): BranchAvailabilityRule
    {
        return BranchAvailabilityRule::create(array_merge([
            'branch_id' => $branch->id,
            'is_booking_enabled' => true,
            'booking_duration_minutes' => 90,
            'min_advance_minutes' => 60,
            'max_advance_days' => 30,
            'open_time' => '10:00:00',
            'close_time' => '23:00:00',
            'mon' => true,
            'tue' => true,
            'wed' => true,
            'thu' => true,
            'fri' => true,
            'sat' => true,
            'sun' => true,
            'notes' => null,
        ], $overrides));
    }

    /**
     * @return array{restaurant: Restaurant, branch: Branch, area: SeatingArea, table: RestaurantTable}
     */
    private function seedRestaurantGraph(string $slug): array
    {
        $restaurant = Restaurant::create([
            'name' => 'Filament Manual R',
            'slug' => $slug,
            'status' => RestaurantStatus::Active,
        ]);

        $branch = Branch::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Main',
            'code' => 'main',
            'status' => BranchStatus::Active,
        ]);

        $area = SeatingArea::create([
            'branch_id' => $branch->id,
            'name' => 'Indoor',
            'code' => 'indoor',
            'type' => 'indoor',
            'status' => 'active',
        ]);

        $table = RestaurantTable::create([
            'seating_area_id' => $area->id,
            'label' => 'T9',
            'capacity' => 4,
            'status' => TableStatus::Active,
        ]);

        return compact('restaurant', 'branch', 'area', 'table');
    }

    private function makeCustomer(string $phone): User
    {
        $email = str_replace(['+', ' '], '', $phone).'@manual-booking.test';

        $user = User::create([
            'name' => 'Booking Tester',
            'phone' => $phone,
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
        $user->syncRoles(['customer']);

        return $user;
    }

    public function test_platform_manual_booking_create_page_shows_manual_fields(): void
    {
        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        $this->get('/platform/bookings/create')
            ->assertOk()
            ->assertSee('Customer phone')
            ->assertSee('Restaurant')
            ->assertSee('Branch')
            ->assertSee('Event (optional)')
            ->assertSee('Seating Area')
            ->assertSee('Starts at');
    }

    public function test_platform_manual_book_create_surfaces_availability_validation_errors(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-03 12:00:00', 'UTC'));

        $data = $this->seedRestaurantGraph('filament-platform-invalid-hours');
        $this->createAvailabilityRule($data['branch']);

        $customer = $this->makeCustomer('+15550088101');

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformCreateBooking::class)
            ->set('data.customer_phone', $customer->phone)
            ->set('data.customer_exists', true)
            ->set('data.restaurant_id', $data['restaurant']->id)
            ->set('data.branch_id', $data['branch']->id)
            ->set('data.party_size', 2)
            ->set('data.starts_at', '2026-06-04 09:30:00')
            ->call('create')
            ->assertHasErrors(['data.starts_at']);
    }

    public function test_platform_manual_booking_without_table_succeeds_via_filament_create(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-03 12:00:00', 'UTC'));

        $data = $this->seedRestaurantGraph('filament-no-table');
        $this->createAvailabilityRule($data['branch']);

        $customer = $this->makeCustomer('+15550088102');

        $event = RestaurantEvent::create([
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'title' => 'E',
            'slug' => 'filament-platform-event-1',
            'starts_at' => Carbon::now()->addDays(2),
            'status' => RestaurantEvent::STATUS_PUBLISHED,
            'booking_mode' => RestaurantEvent::BOOKING_MODE_EVENT,
        ]);

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformCreateBooking::class)
            ->set('data.customer_phone', $customer->phone)
            ->set('data.customer_exists', true)
            ->set('data.restaurant_id', $data['restaurant']->id)
            ->set('data.branch_id', $data['branch']->id)
            ->set('data.restaurant_event_id', $event->id)
            ->set('data.seating_area_id', null)
            ->set('data.restaurant_table_id', null)
            ->set('data.party_size', 2)
            ->set('data.starts_at', '2026-06-04 14:00:00')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('bookings', [
            'branch_id' => $data['branch']->id,
            'party_size' => 2,
            'restaurant_table_id' => null,
            'restaurant_event_id' => $event->id,
        ]);
    }

    public function test_platform_manual_booking_with_table_succeeds_via_filament_create(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-03 12:00:00', 'UTC'));

        $data = $this->seedRestaurantGraph('filament-with-table');
        $this->createAvailabilityRule($data['branch']);

        $customer = $this->makeCustomer('+15550088103');

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformCreateBooking::class)
            ->set('data.customer_phone', $customer->phone)
            ->set('data.customer_exists', true)
            ->set('data.restaurant_id', $data['restaurant']->id)
            ->set('data.branch_id', $data['branch']->id)
            ->set('data.seating_area_id', $data['area']->id)
            ->set('data.restaurant_table_id', $data['table']->id)
            ->set('data.party_size', 2)
            ->set('data.starts_at', '2026-06-04 14:00:00')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('bookings', [
            'branch_id' => $data['branch']->id,
            'restaurant_table_id' => $data['table']->id,
        ]);
    }

    public function test_restaurant_panel_manual_booking_surfaces_availability_validation_errors(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-03 12:00:00', 'UTC'));

        $data = $this->seedRestaurantGraph('filament-rest-invalid');
        $this->createAvailabilityRule($data['branch']);

        $customer = $this->makeCustomer('+15550088104');

        $owner = User::where('email', 'restaurant_owner@eventaat.test')->firstOrFail();
        RestaurantStaffAssignment::create([
            'user_id' => $owner->id,
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => null,
            'role' => RestaurantStaffRole::RestaurantOwner,
            'status' => 'active',
        ]);

        $ownerForPanel = User::query()->findOrFail($owner->id);

        Filament::setCurrentPanel('restaurant');
        Livewire::actingAs($ownerForPanel, 'web');

        Livewire::test(RestaurantCreateBooking::class)
            ->set('data.customer_phone', $customer->phone)
            ->set('data.customer_exists', true)
            ->set('data.restaurant_id', $data['restaurant']->id)
            ->set('data.branch_id', $data['branch']->id)
            ->set('data.party_size', 2)
            ->set('data.starts_at', '2026-06-04 09:30:00')
            ->call('create')
            ->assertHasErrors(['data.starts_at']);
    }

    public function test_restaurant_manual_booking_without_table_succeeds_via_filament_create(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-03 12:00:00', 'UTC'));

        $data = $this->seedRestaurantGraph('filament-rest-ok');
        $this->createAvailabilityRule($data['branch']);

        $customer = $this->makeCustomer('+15550088105');

        $owner = User::where('email', 'restaurant_owner@eventaat.test')->firstOrFail();
        RestaurantStaffAssignment::create([
            'user_id' => $owner->id,
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => null,
            'role' => RestaurantStaffRole::RestaurantOwner,
            'status' => 'active',
        ]);

        $ownerForPanel = User::query()->findOrFail($owner->id);

        Filament::setCurrentPanel('restaurant');
        Livewire::actingAs($ownerForPanel, 'web');

        Livewire::test(RestaurantCreateBooking::class)
            ->set('data.customer_phone', $customer->phone)
            ->set('data.customer_exists', true)
            ->set('data.restaurant_id', $data['restaurant']->id)
            ->set('data.branch_id', $data['branch']->id)
            ->set('data.seating_area_id', null)
            ->set('data.restaurant_table_id', null)
            ->set('data.party_size', 2)
            ->set('data.starts_at', '2026-06-04 14:00:00')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('bookings', [
            'branch_id' => $data['branch']->id,
            'restaurant_table_id' => null,
        ]);
    }

    public function test_dependent_select_queries_return_expected_active_scoped_records(): void
    {
        $data = $this->seedRestaurantGraph('filament-query-check');

        $areaCount = SeatingArea::query()
            ->where('branch_id', $data['branch']->id)
            ->where('status', 'active')
            ->count();

        $tableCount = RestaurantTable::query()
            ->where('status', TableStatus::Active->value)
            ->whereHas('seatingArea', function ($q) use ($data) {
                $q->where('branch_id', $data['branch']->id)
                    ->where('status', 'active');
            })
            ->count();

        $this->assertGreaterThan(0, $areaCount);
        $this->assertGreaterThan(0, $tableCount);
    }

    public function test_platform_filament_surfaces_booking_disabled_error_on_branch_id(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-03 12:00:00', 'UTC'));

        $data = $this->seedRestaurantGraph('filament-disabled-branch');
        $this->createAvailabilityRule($data['branch'], ['is_booking_enabled' => false]);

        $customer = $this->makeCustomer('+15550088106');

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformCreateBooking::class)
            ->set('data.customer_phone', $customer->phone)
            ->set('data.customer_exists', true)
            ->set('data.restaurant_id', $data['restaurant']->id)
            ->set('data.branch_id', $data['branch']->id)
            ->set('data.party_size', 2)
            ->set('data.starts_at', '2026-06-04 14:00:00')
            ->call('create')
            ->assertHasErrors(['data.branch_id']);
    }
}
