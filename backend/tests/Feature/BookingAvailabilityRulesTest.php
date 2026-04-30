<?php

namespace Tests\Feature;

use App\Enums\BranchStatus;
use App\Enums\RestaurantStatus;
use App\Enums\TableStatus;
use App\Models\Branch;
use App\Models\BranchAvailabilityRule;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\SeatingArea;
use App\Models\User;
use App\Services\Bookings\ManualBookingCreationService;
use Database\Seeders\RolesAndTestUsersSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BookingAvailabilityRulesTest extends TestCase
{
    use DatabaseMigrations;

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

    /**
     * @return array{restaurant: Restaurant, branch: Branch, area: SeatingArea, table: RestaurantTable}
     */
    private function makeActiveRestaurantGraph(): array
    {
        $restaurant = Restaurant::create([
            'name' => 'R Avail',
            'slug' => 'r-avail',
            'status' => RestaurantStatus::Active,
        ]);

        $branch = Branch::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'B Avail',
            'code' => 'b',
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
            'label' => 'T1',
            'capacity' => 4,
            'status' => TableStatus::Active,
        ]);

        return compact('restaurant', 'branch', 'area', 'table');
    }

    private function makeCustomer(string $email, string $phone): User
    {
        $user = User::create([
            'name' => '',
            'phone' => $phone,
            'email' => $email,
            'password' => Hash::make('x'),
        ]);
        $user->syncRoles(['customer']);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createRuleForBranch(Branch $branch, array $overrides = []): BranchAvailabilityRule
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

    public function test_mobile_booking_rejected_when_branch_booking_disabled(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-03 12:00:00', 'UTC'));

        $data = $this->makeActiveRestaurantGraph();
        $this->createRuleForBranch($data['branch'], ['is_booking_enabled' => false]);

        $customer = $this->makeCustomer('disabled@avail.test', '+15550020001');
        $token = $customer->createToken('mobile')->plainTextToken;

        $startsAt = Carbon::parse('2026-06-04 14:00:00', 'UTC')->toISOString();

        $this->withToken($token)->postJson('/api/mobile/bookings', [
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'starts_at' => $startsAt,
            'party_size' => 2,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['branch_id']);
    }

    public function test_mobile_booking_rejected_before_min_advance_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-03 12:00:00', 'UTC'));

        $data = $this->makeActiveRestaurantGraph();
        $this->createRuleForBranch($data['branch'], ['min_advance_minutes' => 120]);

        $customer = $this->makeCustomer('minadv@avail.test', '+15550020002');
        $token = $customer->createToken('mobile')->plainTextToken;

        $startsAt = Carbon::parse('2026-06-03 13:00:00', 'UTC')->toISOString();

        $this->withToken($token)->postJson('/api/mobile/bookings', [
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'starts_at' => $startsAt,
            'party_size' => 2,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['starts_at']);
    }

    public function test_mobile_booking_rejected_beyond_max_advance_days(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-03 12:00:00', 'UTC'));

        $data = $this->makeActiveRestaurantGraph();
        $this->createRuleForBranch($data['branch'], ['max_advance_days' => 7]);

        $customer = $this->makeCustomer('maxadv@avail.test', '+15550020003');
        $token = $customer->createToken('mobile')->plainTextToken;

        $startsAt = Carbon::parse('2026-06-15 14:00:00', 'UTC')->toISOString();

        $this->withToken($token)->postJson('/api/mobile/bookings', [
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'starts_at' => $startsAt,
            'party_size' => 2,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['starts_at']);
    }

    public function test_mobile_booking_rejected_on_inactive_weekday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-03 12:00:00', 'UTC')); // Wednesday

        $data = $this->makeActiveRestaurantGraph();
        $this->createRuleForBranch($data['branch'], ['wed' => false]);

        $customer = $this->makeCustomer('weekday@avail.test', '+15550020004');
        $token = $customer->createToken('mobile')->plainTextToken;

        $startsAt = Carbon::parse('2026-06-03 14:00:00', 'UTC')->toISOString();

        $this->withToken($token)->postJson('/api/mobile/bookings', [
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'starts_at' => $startsAt,
            'party_size' => 2,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['starts_at']);
    }

    public function test_mobile_booking_rejected_outside_open_close_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-03 12:00:00', 'UTC'));

        $data = $this->makeActiveRestaurantGraph();
        $this->createRuleForBranch($data['branch'], [
            'open_time' => '10:00:00',
            'close_time' => '22:00:00',
        ]);

        $customer = $this->makeCustomer('hours@avail.test', '+15550020005');
        $token = $customer->createToken('mobile')->plainTextToken;

        $startsAt = Carbon::parse('2026-06-04 09:30:00', 'UTC')->toISOString();

        $this->withToken($token)->postJson('/api/mobile/bookings', [
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'starts_at' => $startsAt,
            'party_size' => 2,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['starts_at']);
    }

    public function test_mobile_booking_accepted_inside_branch_availability_without_table(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-03 12:00:00', 'UTC'));

        $data = $this->makeActiveRestaurantGraph();
        $this->createRuleForBranch($data['branch']);

        $customer = $this->makeCustomer('ok@avail.test', '+15550020006');
        $token = $customer->createToken('mobile')->plainTextToken;

        $startsAt = Carbon::parse('2026-06-04 14:00:00', 'UTC')->toISOString();

        $this->withToken($token)->postJson('/api/mobile/bookings', [
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'starts_at' => $startsAt,
            'party_size' => 2,
        ])
            ->assertStatus(201)
            ->assertJsonPath('booking.status', 'pending');
    }

    public function test_manual_booking_creation_uses_same_availability_validation(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-03 12:00:00', 'UTC'));

        $data = $this->makeActiveRestaurantGraph();
        $this->createRuleForBranch($data['branch'], ['wed' => false]);

        try {
            app(ManualBookingCreationService::class)->create([
                'customer_phone' => '+15550020901',
                'customer_name' => 'Staff Test',
                'restaurant_id' => $data['restaurant']->id,
                'branch_id' => $data['branch']->id,
                'starts_at' => Carbon::parse('2026-06-03 14:00:00', 'UTC'),
                'party_size' => 2,
            ]);
            $this->fail('Expected ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('starts_at', $e->errors());
        }
    }

    public function test_conflict_prevention_still_applies_when_availability_rule_present(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-03 12:00:00', 'UTC'));

        $data = $this->makeActiveRestaurantGraph();
        $this->createRuleForBranch($data['branch'], [
            'min_advance_minutes' => 0,
            'open_time' => null,
            'close_time' => null,
        ]);

        $startsAt = Carbon::parse('2026-06-04 18:00:00', 'UTC');

        app(ManualBookingCreationService::class)->create([
            'customer_phone' => '+15550020902',
            'customer_name' => 'First',
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'restaurant_table_id' => $data['table']->id,
            'starts_at' => $startsAt,
            'party_size' => 2,
        ]);

        $this->expectException(ValidationException::class);
        app(ManualBookingCreationService::class)->create([
            'customer_phone' => '+15550020903',
            'customer_name' => 'Second',
            'restaurant_id' => $data['restaurant']->id,
            'branch_id' => $data['branch']->id,
            'restaurant_table_id' => $data['table']->id,
            'starts_at' => $startsAt,
            'party_size' => 2,
        ]);
    }
}
