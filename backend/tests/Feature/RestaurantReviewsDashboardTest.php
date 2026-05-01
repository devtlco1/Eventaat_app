<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\BranchStatus;
use App\Enums\RestaurantStaffRole;
use App\Enums\RestaurantStatus;
use App\Filament\Platform\Resources\RestaurantReviews\Pages\CreateRestaurantReview as PlatformCreateRestaurantReview;
use App\Filament\Platform\Resources\RestaurantReviews\Pages\ListRestaurantReviews as PlatformListRestaurantReviews;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantReview;
use App\Models\RestaurantStaffAssignment;
use App\Models\User;
use Database\Seeders\RolesAndTestUsersSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class RestaurantReviewsDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
    }

    /**
     * @return array{a: Restaurant, b: Restaurant, aBranch: Branch, bBranch: Branch, aWide: RestaurantReview, aBranchReview: RestaurantReview, bBranchReview: RestaurantReview}
     */
    private function seedRestaurantsAndReviews(): array
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

        $aWide = RestaurantReview::create([
            'restaurant_id' => $a->id,
            'branch_id' => null,
            'customer_name' => 'Wide Reviewer',
            'rating' => 5,
            'comment' => 'A wide published review',
            'status' => RestaurantReview::STATUS_PUBLISHED,
            'source' => RestaurantReview::SOURCE_DASHBOARD,
        ]);

        $aBranchReview = RestaurantReview::create([
            'restaurant_id' => $a->id,
            'branch_id' => $aBranch->id,
            'customer_name' => 'Branch Reviewer A',
            'rating' => 4,
            'comment' => 'A branch published review',
            'status' => RestaurantReview::STATUS_PUBLISHED,
            'source' => RestaurantReview::SOURCE_DASHBOARD,
        ]);

        $bBranchReview = RestaurantReview::create([
            'restaurant_id' => $b->id,
            'branch_id' => $bBranch->id,
            'customer_name' => 'Branch Reviewer B',
            'rating' => 3,
            'comment' => 'B branch published review',
            'status' => RestaurantReview::STATUS_PUBLISHED,
            'source' => RestaurantReview::SOURCE_DASHBOARD,
        ]);

        return compact('a', 'b', 'aBranch', 'bBranch', 'aWide', 'aBranchReview', 'bBranchReview');
    }

    private function assertDeniedOrNotFound(int $status): void
    {
        $this->assertTrue(in_array($status, [403, 404], true), "Expected 403/404, got {$status}");
    }

    public function test_platform_can_list_all_reviews(): void
    {
        $data = $this->seedRestaurantsAndReviews();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        $index = $this->get('/platform/restaurant-reviews');
        $index->assertOk();
        $index->assertSee($data['aWide']->customer_name);
        $index->assertSee($data['aBranchReview']->customer_name);
        $index->assertSee($data['bBranchReview']->customer_name);
        $index->assertSee('/platform/restaurant-reviews/create');
    }

    public function test_platform_super_admin_can_access_review_create_page(): void
    {
        $this->seedRestaurantsAndReviews();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        $this->get('/platform/restaurant-reviews/create')->assertOk();
    }

    public function test_platform_can_create_review_with_valid_attributes(): void
    {
        $data = $this->seedRestaurantsAndReviews();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformCreateRestaurantReview::class)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', $data['aBranch']->id)
            ->set('data.rating', 5)
            ->set('data.status', RestaurantReview::STATUS_PENDING_REVIEW)
            ->set('data.source', RestaurantReview::SOURCE_DASHBOARD)
            ->set('data.customer_name', 'Manual Customer')
            ->set('data.customer_phone', '+10000000003')
            ->set('data.comment', 'Manually entered review')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('restaurant_reviews', [
            'restaurant_id' => $data['a']->id,
            'branch_id' => $data['aBranch']->id,
            'customer_name' => 'Manual Customer',
            'rating' => 5,
            'status' => RestaurantReview::STATUS_PENDING_REVIEW,
            'source' => RestaurantReview::SOURCE_DASHBOARD,
        ]);
    }

    public function test_branch_must_belong_to_selected_restaurant_validation(): void
    {
        $data = $this->seedRestaurantsAndReviews();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformCreateRestaurantReview::class)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', $data['bBranch']->id)
            ->set('data.rating', 4)
            ->set('data.status', RestaurantReview::STATUS_PENDING_REVIEW)
            ->set('data.source', RestaurantReview::SOURCE_DASHBOARD)
            ->set('data.customer_name', 'Bad Branch')
            ->call('create')
            ->assertHasErrors(['data.branch_id']);
    }

    public function test_rating_must_be_between_1_and_5_at_model_level(): void
    {
        $data = $this->seedRestaurantsAndReviews();

        $this->expectException(ValidationException::class);

        RestaurantReview::create([
            'restaurant_id' => $data['a']->id,
            'rating' => 6,
            'customer_name' => 'Bad Rating',
            'status' => RestaurantReview::STATUS_PENDING_REVIEW,
            'source' => RestaurantReview::SOURCE_DASHBOARD,
        ]);
    }

    public function test_invalid_status_is_rejected_at_model_level(): void
    {
        $data = $this->seedRestaurantsAndReviews();

        $this->expectException(ValidationException::class);

        RestaurantReview::create([
            'restaurant_id' => $data['a']->id,
            'rating' => 4,
            'status' => 'unknown_status',
            'source' => RestaurantReview::SOURCE_DASHBOARD,
        ]);
    }

    public function test_invalid_source_is_rejected_at_model_level(): void
    {
        $data = $this->seedRestaurantsAndReviews();

        $this->expectException(ValidationException::class);

        RestaurantReview::create([
            'restaurant_id' => $data['a']->id,
            'rating' => 4,
            'status' => RestaurantReview::STATUS_PENDING_REVIEW,
            'source' => 'sms',
        ]);
    }

    public function test_booking_must_belong_to_restaurant_at_model_level(): void
    {
        $data = $this->seedRestaurantsAndReviews();

        $customer = User::where('email', 'customer@eventaat.test')->firstOrFail();

        $bookingForB = Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $data['b']->id,
            'branch_id' => $data['bBranch']->id,
            'starts_at' => Carbon::now()->addHours(2),
            'party_size' => 2,
            'status' => BookingStatus::Pending,
        ]);

        $this->expectException(ValidationException::class);

        RestaurantReview::create([
            'restaurant_id' => $data['a']->id,
            'booking_id' => $bookingForB->id,
            'rating' => 4,
            'status' => RestaurantReview::STATUS_PENDING_REVIEW,
            'source' => RestaurantReview::SOURCE_DASHBOARD,
        ]);
    }

    public function test_booking_branch_must_match_review_branch_at_model_level(): void
    {
        $data = $this->seedRestaurantsAndReviews();

        $extraBranch = Branch::create([
            'restaurant_id' => $data['a']->id,
            'name' => 'A Second',
            'code' => 'second',
            'status' => BranchStatus::Active,
        ]);

        $customer = User::where('email', 'customer@eventaat.test')->firstOrFail();

        $bookingAtMain = Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $data['a']->id,
            'branch_id' => $data['aBranch']->id,
            'starts_at' => Carbon::now()->addHours(3),
            'party_size' => 2,
            'status' => BookingStatus::Pending,
        ]);

        $this->expectException(ValidationException::class);

        RestaurantReview::create([
            'restaurant_id' => $data['a']->id,
            'branch_id' => $extraBranch->id,
            'booking_id' => $bookingAtMain->id,
            'rating' => 4,
            'status' => RestaurantReview::STATUS_PENDING_REVIEW,
            'source' => RestaurantReview::SOURCE_DASHBOARD,
        ]);
    }

    public function test_user_id_is_inferred_from_booking_when_blank(): void
    {
        $data = $this->seedRestaurantsAndReviews();

        $customer = User::where('email', 'customer@eventaat.test')->firstOrFail();

        $booking = Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $data['a']->id,
            'branch_id' => $data['aBranch']->id,
            'starts_at' => Carbon::now()->addHours(4),
            'party_size' => 2,
            'status' => BookingStatus::Pending,
        ]);

        $review = RestaurantReview::create([
            'restaurant_id' => $data['a']->id,
            'branch_id' => $data['aBranch']->id,
            'booking_id' => $booking->id,
            'rating' => 5,
            'comment' => 'Inferred user',
            'status' => RestaurantReview::STATUS_PUBLISHED,
            'source' => RestaurantReview::SOURCE_DASHBOARD,
        ]);

        $this->assertSame((int) $customer->id, (int) $review->user_id);
    }

    public function test_platform_can_approve_pending_review(): void
    {
        $data = $this->seedRestaurantsAndReviews();

        $review = RestaurantReview::create([
            'restaurant_id' => $data['a']->id,
            'branch_id' => $data['aBranch']->id,
            'customer_name' => 'Pending To Approve',
            'rating' => 4,
            'status' => RestaurantReview::STATUS_PENDING_REVIEW,
            'source' => RestaurantReview::SOURCE_DASHBOARD,
        ]);

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformListRestaurantReviews::class)
            ->callTableAction('approve', $review);

        $this->assertDatabaseHas('restaurant_reviews', [
            'id' => $review->id,
            'status' => RestaurantReview::STATUS_PUBLISHED,
        ]);
    }

    public function test_platform_can_reject_pending_review(): void
    {
        $data = $this->seedRestaurantsAndReviews();

        $review = RestaurantReview::create([
            'restaurant_id' => $data['a']->id,
            'branch_id' => $data['aBranch']->id,
            'customer_name' => 'Pending To Reject',
            'rating' => 2,
            'status' => RestaurantReview::STATUS_PENDING_REVIEW,
            'source' => RestaurantReview::SOURCE_DASHBOARD,
        ]);

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformListRestaurantReviews::class)
            ->callTableAction('reject', $review);

        $this->assertDatabaseHas('restaurant_reviews', [
            'id' => $review->id,
            'status' => RestaurantReview::STATUS_REJECTED,
        ]);
    }

    public function test_platform_can_hide_published_review(): void
    {
        $data = $this->seedRestaurantsAndReviews();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformListRestaurantReviews::class)
            ->callTableAction('hide', $data['aBranchReview']);

        $this->assertDatabaseHas('restaurant_reviews', [
            'id' => $data['aBranchReview']->id,
            'status' => RestaurantReview::STATUS_HIDDEN,
        ]);
    }

    public function test_restaurant_owner_sees_assigned_restaurant_reviews_including_restaurant_wide(): void
    {
        $data = $this->seedRestaurantsAndReviews();

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

        $index = $this->get('/restaurant/restaurant-reviews');
        $index->assertOk();
        $index->assertSee($data['aWide']->customer_name);
        $index->assertSee($data['aBranchReview']->customer_name);
        $index->assertDontSee($data['bBranchReview']->customer_name);

        $index->assertDontSee('/restaurant/restaurant-reviews/create');

        $this->get("/restaurant/restaurant-reviews/{$data['bBranchReview']->id}")
            ->tap(fn ($resp) => $this->assertDeniedOrNotFound($resp->getStatusCode()));
    }

    public function test_branch_manager_sees_branch_scoped_reviews_only(): void
    {
        $data = $this->seedRestaurantsAndReviews();

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

        $index = $this->get('/restaurant/restaurant-reviews');
        $index->assertOk();
        $index->assertSee($data['aBranchReview']->customer_name);
        $index->assertDontSee($data['aWide']->customer_name);
        $index->assertDontSee($data['bBranchReview']->customer_name);

        $this->get("/restaurant/restaurant-reviews/{$data['aWide']->id}")
            ->tap(fn ($resp) => $this->assertDeniedOrNotFound($resp->getStatusCode()));
        $this->get("/restaurant/restaurant-reviews/{$data['bBranchReview']->id}")
            ->tap(fn ($resp) => $this->assertDeniedOrNotFound($resp->getStatusCode()));
    }

    public function test_restaurant_host_cannot_see_restaurant_wide_review(): void
    {
        $data = $this->seedRestaurantsAndReviews();

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

        $this->get("/restaurant/restaurant-reviews/{$data['aWide']->id}")
            ->tap(fn ($resp) => $this->assertDeniedOrNotFound($resp->getStatusCode()));
    }

    public function test_restaurant_panel_has_no_review_create_route(): void
    {
        $data = $this->seedRestaurantsAndReviews();

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

        $resp = $this->get('/restaurant/restaurant-reviews/create');
        $this->assertDeniedOrNotFound($resp->getStatusCode());

        $editResp = $this->get("/restaurant/restaurant-reviews/{$data['aWide']->id}/edit");
        $this->assertDeniedOrNotFound($editResp->getStatusCode());
    }

    public function test_customer_cannot_access_review_dashboards(): void
    {
        $customer = User::where('email', 'customer@eventaat.test')->firstOrFail();
        $this->actingAs($customer);

        $this->get('/platform/restaurant-reviews')->assertForbidden();
        $this->get('/restaurant/restaurant-reviews')->assertForbidden();
    }
}
