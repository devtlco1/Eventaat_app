<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\BranchStatus;
use App\Enums\RestaurantStatus;
use App\Enums\TableStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantReview;
use App\Models\RestaurantTable;
use App\Models\SeatingArea;
use App\Models\User;
use Database\Seeders\RolesAndTestUsersSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileReviewsApiTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
    }

    /**
     * @return array{restaurant: Restaurant, branch: Branch}
     */
    private function makeActiveRestaurantGraph(): array
    {
        $restaurant = Restaurant::create([
            'name' => 'Review Cafe',
            'slug' => 'review-cafe',
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

        RestaurantTable::create([
            'seating_area_id' => $area->id,
            'label' => 'T1',
            'capacity' => 4,
            'status' => TableStatus::Active,
        ]);

        return compact('restaurant', 'branch');
    }

    private function makeCustomer(string $email, string $phone, string $name = 'Review Person'): User
    {
        $user = User::create([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'password' => Hash::make('x'),
        ]);
        $user->syncRoles(['customer']);

        return $user;
    }

    public function test_unauthenticated_cannot_submit_review(): void
    {
        $graph = $this->makeActiveRestaurantGraph();
        $customer = $this->makeCustomer('rv-unauth@mobile.eventaat.test', '+15550020099');
        $booking = Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $graph['restaurant']->id,
            'branch_id' => $graph['branch']->id,
            'starts_at' => Carbon::now()->subDay(),
            'party_size' => 2,
            'status' => BookingStatus::Completed,
        ]);

        $this->postJson("/api/mobile/bookings/{$booking->id}/review", [
            'rating' => 5,
            'comment' => 'Nice',
        ])->assertUnauthorized();
    }

    public function test_customer_can_submit_review_for_own_completed_booking(): void
    {
        $graph = $this->makeActiveRestaurantGraph();
        $customer = $this->makeCustomer('rv1@mobile.eventaat.test', '+15550020001', 'Alice Review');
        $token = $customer->createToken('mobile')->plainTextToken;

        $booking = Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $graph['restaurant']->id,
            'branch_id' => $graph['branch']->id,
            'starts_at' => Carbon::now()->subDay(),
            'party_size' => 2,
            'status' => BookingStatus::Completed,
        ]);

        $resp = $this->withToken($token)->postJson("/api/mobile/bookings/{$booking->id}/review", [
            'rating' => 5,
            'comment' => 'Great experience',
        ])->assertCreated();

        $resp->assertJsonPath('review.status', 'pending_review');
        $resp->assertJsonPath('review.source', 'mobile');
        $resp->assertJsonPath('review.rating', 5);
        $resp->assertJsonPath('review.booking_id', $booking->id);

        $this->assertDatabaseHas('restaurant_reviews', [
            'booking_id' => $booking->id,
            'user_id' => $customer->id,
            'restaurant_id' => $graph['restaurant']->id,
            'branch_id' => $graph['branch']->id,
            'status' => RestaurantReview::STATUS_PENDING_REVIEW,
            'source' => RestaurantReview::SOURCE_MOBILE,
            'customer_name' => 'Alice Review',
        ]);
    }

    public function test_customer_cannot_review_non_completed_booking(): void
    {
        $graph = $this->makeActiveRestaurantGraph();
        $customer = $this->makeCustomer('rv2@mobile.eventaat.test', '+15550020002');
        $token = $customer->createToken('mobile')->plainTextToken;

        $booking = Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $graph['restaurant']->id,
            'branch_id' => $graph['branch']->id,
            'starts_at' => Carbon::now()->addHour(),
            'party_size' => 2,
            'status' => BookingStatus::Pending,
        ]);

        $this->withToken($token)->postJson("/api/mobile/bookings/{$booking->id}/review", [
            'rating' => 4,
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.booking.0', 'The booking must be completed before submitting a review.');
    }

    public function test_customer_cannot_review_another_customers_booking(): void
    {
        $graph = $this->makeActiveRestaurantGraph();
        $owner = $this->makeCustomer('rv3@mobile.eventaat.test', '+15550020003');
        $other = $this->makeCustomer('rv4@mobile.eventaat.test', '+15550020004');
        $tokenOther = $other->createToken('mobile')->plainTextToken;

        $booking = Booking::create([
            'customer_id' => $owner->id,
            'restaurant_id' => $graph['restaurant']->id,
            'branch_id' => $graph['branch']->id,
            'starts_at' => Carbon::now()->subDay(),
            'party_size' => 2,
            'status' => BookingStatus::Completed,
        ]);

        $this->withToken($tokenOther)->postJson("/api/mobile/bookings/{$booking->id}/review", [
            'rating' => 5,
        ])->assertNotFound();
    }

    public function test_duplicate_review_for_same_booking_returns_422(): void
    {
        $graph = $this->makeActiveRestaurantGraph();
        $customer = $this->makeCustomer('rv5@mobile.eventaat.test', '+15550020005');
        $token = $customer->createToken('mobile')->plainTextToken;

        $booking = Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $graph['restaurant']->id,
            'branch_id' => $graph['branch']->id,
            'starts_at' => Carbon::now()->subDay(),
            'party_size' => 2,
            'status' => BookingStatus::Completed,
        ]);

        $this->withToken($token)->postJson("/api/mobile/bookings/{$booking->id}/review", [
            'rating' => 5,
        ])->assertCreated();

        $this->withToken($token)->postJson("/api/mobile/bookings/{$booking->id}/review", [
            'rating' => 4,
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.booking.0', 'A review already exists for this booking.');
    }

    public function test_customer_name_fallback_when_user_name_blank(): void
    {
        $graph = $this->makeActiveRestaurantGraph();
        $customer = User::create([
            'name' => '',
            'phone' => '+15550020006',
            'email' => 'rv6@mobile.eventaat.test',
            'password' => Hash::make('x'),
        ]);
        $customer->syncRoles(['customer']);
        $token = $customer->createToken('mobile')->plainTextToken;

        $booking = Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $graph['restaurant']->id,
            'branch_id' => $graph['branch']->id,
            'starts_at' => Carbon::now()->subDay(),
            'party_size' => 2,
            'status' => BookingStatus::Completed,
        ]);

        $this->withToken($token)->postJson("/api/mobile/bookings/{$booking->id}/review", [
            'rating' => 3,
        ])->assertCreated();

        $this->assertDatabaseHas('restaurant_reviews', [
            'booking_id' => $booking->id,
            'customer_name' => 'Customer',
        ]);
    }

    public function test_me_reviews_returns_only_own_reviews(): void
    {
        $graph = $this->makeActiveRestaurantGraph();
        $a = $this->makeCustomer('rv7a@mobile.eventaat.test', '+15550020007');
        $b = $this->makeCustomer('rv7b@mobile.eventaat.test', '+15550020008');

        $bookingA = Booking::create([
            'customer_id' => $a->id,
            'restaurant_id' => $graph['restaurant']->id,
            'branch_id' => $graph['branch']->id,
            'starts_at' => Carbon::now()->subDays(2),
            'party_size' => 2,
            'status' => BookingStatus::Completed,
        ]);
        $bookingB = Booking::create([
            'customer_id' => $b->id,
            'restaurant_id' => $graph['restaurant']->id,
            'branch_id' => $graph['branch']->id,
            'starts_at' => Carbon::now()->subDay(),
            'party_size' => 2,
            'status' => BookingStatus::Completed,
        ]);

        RestaurantReview::create([
            'restaurant_id' => $graph['restaurant']->id,
            'branch_id' => $graph['branch']->id,
            'booking_id' => $bookingA->id,
            'user_id' => $a->id,
            'customer_name' => 'A',
            'rating' => 5,
            'comment' => 'A only',
            'status' => RestaurantReview::STATUS_PENDING_REVIEW,
            'source' => RestaurantReview::SOURCE_MOBILE,
        ]);
        RestaurantReview::create([
            'restaurant_id' => $graph['restaurant']->id,
            'branch_id' => $graph['branch']->id,
            'booking_id' => $bookingB->id,
            'user_id' => $b->id,
            'customer_name' => 'B',
            'rating' => 4,
            'comment' => 'B only',
            'status' => RestaurantReview::STATUS_PENDING_REVIEW,
            'source' => RestaurantReview::SOURCE_MOBILE,
        ]);

        $tokenA = $a->createToken('mobile')->plainTextToken;
        $list = $this->withToken($tokenA)->getJson('/api/mobile/me/reviews')->assertOk();

        $ids = collect($list->json('data'))->pluck('id')->all();
        $this->assertCount(1, $ids);
        $this->assertSame('A only', $list->json('data.0.comment'));
    }

    public function test_me_review_show_returns_404_for_other_customers_review(): void
    {
        $graph = $this->makeActiveRestaurantGraph();
        $a = $this->makeCustomer('rv8a@mobile.eventaat.test', '+15550020009');
        $b = $this->makeCustomer('rv8b@mobile.eventaat.test', '+15550020010');

        $bookingB = Booking::create([
            'customer_id' => $b->id,
            'restaurant_id' => $graph['restaurant']->id,
            'branch_id' => $graph['branch']->id,
            'starts_at' => Carbon::now()->subDay(),
            'party_size' => 2,
            'status' => BookingStatus::Completed,
        ]);

        $reviewB = RestaurantReview::create([
            'restaurant_id' => $graph['restaurant']->id,
            'branch_id' => $graph['branch']->id,
            'booking_id' => $bookingB->id,
            'user_id' => $b->id,
            'customer_name' => 'B',
            'rating' => 5,
            'status' => RestaurantReview::STATUS_PENDING_REVIEW,
            'source' => RestaurantReview::SOURCE_MOBILE,
        ]);

        $tokenA = $a->createToken('mobile')->plainTextToken;
        $this->withToken($tokenA)->getJson("/api/mobile/me/reviews/{$reviewB->id}")
            ->assertNotFound();
    }

    public function test_restaurant_reviews_returns_only_published(): void
    {
        $graph = $this->makeActiveRestaurantGraph();
        $customer = $this->makeCustomer('rv9@mobile.eventaat.test', '+15550020011');

        $booking1 = Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $graph['restaurant']->id,
            'branch_id' => $graph['branch']->id,
            'starts_at' => Carbon::now()->subDays(3),
            'party_size' => 2,
            'status' => BookingStatus::Completed,
        ]);
        $booking2 = Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $graph['restaurant']->id,
            'branch_id' => $graph['branch']->id,
            'starts_at' => Carbon::now()->subDays(2),
            'party_size' => 2,
            'status' => BookingStatus::Completed,
        ]);

        RestaurantReview::create([
            'restaurant_id' => $graph['restaurant']->id,
            'branch_id' => $graph['branch']->id,
            'booking_id' => $booking1->id,
            'user_id' => $customer->id,
            'customer_name' => 'Pub User',
            'rating' => 5,
            'comment' => 'Published text',
            'status' => RestaurantReview::STATUS_PUBLISHED,
            'source' => RestaurantReview::SOURCE_MOBILE,
            'admin_notes' => 'secret',
            'customer_phone' => '+19999999999',
        ]);

        RestaurantReview::create([
            'restaurant_id' => $graph['restaurant']->id,
            'branch_id' => $graph['branch']->id,
            'booking_id' => $booking2->id,
            'user_id' => $customer->id,
            'customer_name' => 'Hidden Pending',
            'rating' => 2,
            'comment' => 'Should not list',
            'status' => RestaurantReview::STATUS_PENDING_REVIEW,
            'source' => RestaurantReview::SOURCE_MOBILE,
        ]);

        $token = $customer->createToken('mobile')->plainTextToken;
        $resp = $this->withToken($token)->getJson('/api/mobile/restaurants/review-cafe/reviews')->assertOk();

        $this->assertCount(1, $resp->json('data'));
        $this->assertSame('Published text', $resp->json('data.0.comment'));
    }

    public function test_restaurant_reviews_public_payload_has_no_sensitive_fields(): void
    {
        $graph = $this->makeActiveRestaurantGraph();
        $customer = $this->makeCustomer('rv10@mobile.eventaat.test', '+15550020012');

        $booking = Booking::create([
            'customer_id' => $customer->id,
            'restaurant_id' => $graph['restaurant']->id,
            'branch_id' => $graph['branch']->id,
            'starts_at' => Carbon::now()->subDay(),
            'party_size' => 2,
            'status' => BookingStatus::Completed,
        ]);

        RestaurantReview::create([
            'restaurant_id' => $graph['restaurant']->id,
            'branch_id' => $graph['branch']->id,
            'booking_id' => $booking->id,
            'user_id' => $customer->id,
            'customer_name' => 'Shown Name',
            'customer_phone' => '+15550029999',
            'rating' => 5,
            'comment' => 'Hello',
            'status' => RestaurantReview::STATUS_PUBLISHED,
            'source' => RestaurantReview::SOURCE_MOBILE,
            'admin_notes' => 'internal only',
        ]);

        $token = $customer->createToken('mobile')->plainTextToken;
        $resp = $this->withToken($token)->getJson('/api/mobile/restaurants/review-cafe/reviews')->assertOk();

        $row = $resp->json('data.0');
        $this->assertNotNull($row);
        $allowedKeys = ['id', 'customer_name', 'rating', 'comment', 'created_at'];
        $this->assertSame($allowedKeys, array_keys($row));
        $this->assertArrayNotHasKey('customer_phone', $row);
        $this->assertArrayNotHasKey('admin_notes', $row);
        $this->assertArrayNotHasKey('status', $row);
        $this->assertArrayNotHasKey('source', $row);
        $this->assertArrayNotHasKey('booking_id', $row);
        $this->assertArrayNotHasKey('phone', $row);
    }

    public function test_inactive_restaurant_reviews_returns_404(): void
    {
        $graph = $this->makeActiveRestaurantGraph();
        $graph['restaurant']->forceFill(['status' => RestaurantStatus::Inactive])->save();

        $customer = $this->makeCustomer('rv11@mobile.eventaat.test', '+15550020013');
        $token = $customer->createToken('mobile')->plainTextToken;

        $this->withToken($token)->getJson('/api/mobile/restaurants/review-cafe/reviews')
            ->assertNotFound();
    }
}
