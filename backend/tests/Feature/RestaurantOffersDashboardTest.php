<?php

namespace Tests\Feature;

use App\Enums\BranchStatus;
use App\Enums\RestaurantStaffRole;
use App\Enums\RestaurantStatus;
use App\Filament\Platform\Resources\RestaurantOffers\Pages\CreateRestaurantOffer as PlatformCreateRestaurantOffer;
use App\Filament\Restaurant\Resources\RestaurantOffers\Pages\CreateRestaurantOffer as RestaurantCreateRestaurantOffer;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantOffer;
use App\Models\RestaurantStaffAssignment;
use App\Models\User;
use Database\Seeders\RolesAndTestUsersSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RestaurantOffersDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
    }

    private function seedRestaurantsAndOffers(): array
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

        $aRestaurantWide = RestaurantOffer::create([
            'restaurant_id' => $a->id,
            'branch_id' => null,
            'title' => 'A Wide',
            'slug' => 'a-wide',
            'status' => RestaurantOffer::STATUS_PUBLISHED,
            'offer_type' => RestaurantOffer::TYPE_TEXT_ONLY,
            'discount_value' => null,
        ]);

        $aBranchOffer = RestaurantOffer::create([
            'restaurant_id' => $a->id,
            'branch_id' => $aBranch->id,
            'title' => 'A Branch',
            'slug' => 'a-branch',
            'status' => RestaurantOffer::STATUS_PUBLISHED,
            'offer_type' => RestaurantOffer::TYPE_PERCENTAGE,
            'discount_value' => 10,
        ]);

        $bBranchOffer = RestaurantOffer::create([
            'restaurant_id' => $b->id,
            'branch_id' => $bBranch->id,
            'title' => 'B Branch',
            'slug' => 'b-branch',
            'status' => RestaurantOffer::STATUS_PUBLISHED,
            'offer_type' => RestaurantOffer::TYPE_PERCENTAGE,
            'discount_value' => 10,
        ]);

        return compact('a', 'b', 'aBranch', 'bBranch', 'aRestaurantWide', 'aBranchOffer', 'bBranchOffer');
    }

    private function assertDeniedOrNotFound(int $status): void
    {
        $this->assertTrue(in_array($status, [403, 404], true), "Expected 403/404, got {$status}");
    }

    public function test_platform_can_list_all_offers(): void
    {
        $data = $this->seedRestaurantsAndOffers();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        $index = $this->get('/platform/restaurant-offers');
        $index->assertOk();
        $index->assertSee($data['aRestaurantWide']->title);
        $index->assertSee($data['aBranchOffer']->title);
        $index->assertSee($data['bBranchOffer']->title);
    }

    public function test_platform_can_create_percentage_offer_with_valid_discount(): void
    {
        $data = $this->seedRestaurantsAndOffers();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformCreateRestaurantOffer::class)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', $data['aBranch']->id)
            ->set('data.title', 'New Offer')
            ->set('data.slug', 'new-offer')
            ->set('data.status', RestaurantOffer::STATUS_DRAFT)
            ->set('data.offer_type', RestaurantOffer::TYPE_PERCENTAGE)
            ->set('data.discount_value', 20)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('restaurant_offers', [
            'slug' => 'new-offer',
            'restaurant_id' => $data['a']->id,
            'branch_id' => $data['aBranch']->id,
            'offer_type' => RestaurantOffer::TYPE_PERCENTAGE,
        ]);
    }

    public function test_restaurant_owner_sees_only_assigned_restaurant_offers_including_restaurant_wide(): void
    {
        $data = $this->seedRestaurantsAndOffers();

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

        $index = $this->get('/restaurant/restaurant-offers');
        $index->assertOk();
        $index->assertSee($data['aRestaurantWide']->title);
        $index->assertSee($data['aBranchOffer']->title);
        $index->assertDontSee($data['bBranchOffer']->title);

        $this->get("/restaurant/restaurant-offers/{$data['bBranchOffer']->id}")
            ->tap(fn ($resp) => $this->assertDeniedOrNotFound($resp->getStatusCode()));
    }

    public function test_branch_manager_sees_only_branch_scoped_offers_and_cannot_access_restaurant_wide_offer(): void
    {
        $data = $this->seedRestaurantsAndOffers();

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

        $index = $this->get('/restaurant/restaurant-offers');
        $index->assertOk();
        $index->assertSee($data['aBranchOffer']->title);
        $index->assertDontSee($data['aRestaurantWide']->title);
        $index->assertDontSee($data['bBranchOffer']->title);

        $this->get("/restaurant/restaurant-offers/{$data['aRestaurantWide']->id}")
            ->tap(fn ($resp) => $this->assertDeniedOrNotFound($resp->getStatusCode()));

        $this->get("/restaurant/restaurant-offers/{$data['bBranchOffer']->id}")
            ->tap(fn ($resp) => $this->assertDeniedOrNotFound($resp->getStatusCode()));
    }

    public function test_restaurant_host_cannot_access_restaurant_wide_offer(): void
    {
        $data = $this->seedRestaurantsAndOffers();

        $host = User::where('email', 'restaurant_host@eventaat.test')->firstOrFail();
        RestaurantStaffAssignment::create([
            'user_id' => $host->id,
            'restaurant_id' => $data['a']->id,
            'branch_id' => $data['aBranch']->id,
            'role' => RestaurantStaffRole::RestaurantHost,
            'status' => 'active',
        ]);

        Filament::setCurrentPanel('restaurant');
        $this->actingAs($host);

        $this->get("/restaurant/restaurant-offers/{$data['aRestaurantWide']->id}")
            ->tap(fn ($resp) => $this->assertDeniedOrNotFound($resp->getStatusCode()));
    }

    public function test_branch_must_belong_to_selected_restaurant_validation(): void
    {
        $data = $this->seedRestaurantsAndOffers();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformCreateRestaurantOffer::class)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', $data['bBranch']->id)
            ->set('data.title', 'Bad Branch')
            ->set('data.slug', 'bad-branch')
            ->set('data.status', RestaurantOffer::STATUS_DRAFT)
            ->set('data.offer_type', RestaurantOffer::TYPE_TEXT_ONLY)
            ->call('create')
            ->assertHasErrors(['data.branch_id']);
    }

    public function test_percentage_offer_requires_discount_value_between_1_and_100(): void
    {
        $data = $this->seedRestaurantsAndOffers();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformCreateRestaurantOffer::class)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', $data['aBranch']->id)
            ->set('data.title', 'Bad Percent')
            ->set('data.slug', 'bad-percent')
            ->set('data.status', RestaurantOffer::STATUS_DRAFT)
            ->set('data.offer_type', RestaurantOffer::TYPE_PERCENTAGE)
            ->set('data.discount_value', 101)
            ->call('create')
            ->assertHasErrors(['data.discount_value']);
    }

    public function test_fixed_amount_offer_requires_discount_value_greater_than_0(): void
    {
        $data = $this->seedRestaurantsAndOffers();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformCreateRestaurantOffer::class)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', $data['aBranch']->id)
            ->set('data.title', 'Bad Fixed')
            ->set('data.slug', 'bad-fixed')
            ->set('data.status', RestaurantOffer::STATUS_DRAFT)
            ->set('data.offer_type', RestaurantOffer::TYPE_FIXED_AMOUNT)
            ->set('data.discount_value', 0)
            ->call('create')
            ->assertHasErrors(['data.discount_value']);
    }

    public function test_text_only_offer_can_have_no_discount_value(): void
    {
        $data = $this->seedRestaurantsAndOffers();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformCreateRestaurantOffer::class)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', null)
            ->set('data.title', 'Text Only')
            ->set('data.slug', 'text-only')
            ->set('data.status', RestaurantOffer::STATUS_DRAFT)
            ->set('data.offer_type', RestaurantOffer::TYPE_TEXT_ONLY)
            ->set('data.discount_value', null)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('restaurant_offers', [
            'slug' => 'text-only',
            'offer_type' => RestaurantOffer::TYPE_TEXT_ONLY,
            'discount_value' => null,
        ]);
    }

    public function test_ends_at_must_be_after_starts_at(): void
    {
        $data = $this->seedRestaurantsAndOffers();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformCreateRestaurantOffer::class)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', null)
            ->set('data.title', 'Bad Dates')
            ->set('data.slug', 'bad-dates')
            ->set('data.status', RestaurantOffer::STATUS_DRAFT)
            ->set('data.offer_type', RestaurantOffer::TYPE_TEXT_ONLY)
            ->set('data.starts_at', '2026-06-04 12:00:00')
            ->set('data.ends_at', '2026-06-04 11:00:00')
            ->call('create')
            ->assertHasErrors(['data.ends_at']);
    }

    public function test_branch_scoped_user_cannot_create_restaurant_wide_offer(): void
    {
        $data = $this->seedRestaurantsAndOffers();

        $manager = User::where('email', 'branch_manager@eventaat.test')->firstOrFail();
        RestaurantStaffAssignment::create([
            'user_id' => $manager->id,
            'restaurant_id' => $data['a']->id,
            'branch_id' => $data['aBranch']->id,
            'role' => RestaurantStaffRole::BranchManager,
            'status' => 'active',
        ]);

        $managerForPanel = User::query()->findOrFail($manager->id);

        Filament::setCurrentPanel('restaurant');
        Livewire::actingAs($managerForPanel, 'web');

        Livewire::test(RestaurantCreateRestaurantOffer::class)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', null)
            ->set('data.title', 'Manager Wide')
            ->set('data.slug', 'manager-wide')
            ->set('data.status', RestaurantOffer::STATUS_DRAFT)
            ->set('data.offer_type', RestaurantOffer::TYPE_TEXT_ONLY)
            ->call('create')
            ->assertHasErrors(['data.branch_id']);
    }

    public function test_restaurant_branch_scoped_user_cannot_craft_out_of_scope_restaurant_or_branch(): void
    {
        $data = $this->seedRestaurantsAndOffers();

        $manager = User::where('email', 'branch_manager@eventaat.test')->firstOrFail();
        RestaurantStaffAssignment::create([
            'user_id' => $manager->id,
            'restaurant_id' => $data['a']->id,
            'branch_id' => $data['aBranch']->id,
            'role' => RestaurantStaffRole::BranchManager,
            'status' => 'active',
        ]);

        $managerForPanel = User::query()->findOrFail($manager->id);

        Filament::setCurrentPanel('restaurant');
        Livewire::actingAs($managerForPanel, 'web');

        Livewire::test(RestaurantCreateRestaurantOffer::class)
            ->set('data.restaurant_id', $data['b']->id)
            ->set('data.branch_id', $data['bBranch']->id)
            ->set('data.title', 'Crafted')
            ->set('data.slug', 'crafted')
            ->set('data.status', RestaurantOffer::STATUS_DRAFT)
            ->set('data.offer_type', RestaurantOffer::TYPE_TEXT_ONLY)
            ->call('create')
            ->assertHasErrors(['data.restaurant_id', 'data.branch_id']);
    }
}

