<?php

namespace Tests\Feature;

use App\Enums\BranchStatus;
use App\Enums\RestaurantStatus;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantReview;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class MobileRestaurantRatingsApiTest extends TestCase
{
    use DatabaseMigrations;

    private function authCustomer(): array
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'customer@eventaat.test')->firstOrFail();

        return [$user->createToken('mobile')->plainTextToken, $user];
    }

    /** Creates an active restaurant + branch with no reviews. */
    private function freshRestaurant(): array
    {
        $restaurant = Restaurant::create([
            'name' => 'Rating Test Restaurant',
            'slug' => 'rating-test-restaurant',
            'status' => RestaurantStatus::Active,
        ]);

        $branch = Branch::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Main Branch',
            'code' => 'main',
            'status' => BranchStatus::Active,
        ]);

        return [$restaurant, $branch];
    }

    private function addPublishedReview(Restaurant $restaurant, ?Branch $branch, User $user, int $rating): void
    {
        RestaurantReview::create([
            'restaurant_id' => $restaurant->id,
            'branch_id' => $branch?->id,
            'user_id' => $user->id,
            'customer_name' => $user->name ?? 'Customer',
            'customer_phone' => $user->phone,
            'rating' => $rating,
            'status' => RestaurantReview::STATUS_PUBLISHED,
            'source' => RestaurantReview::SOURCE_MOBILE,
        ]);
    }

    public function test_restaurant_list_has_null_avg_rating_and_zero_count_when_no_reviews(): void
    {
        [$token] = $this->authCustomer();
        [$restaurant] = $this->freshRestaurant();

        $resp = $this->withToken($token)->getJson('/api/mobile/restaurants');
        $resp->assertOk();

        $item = collect($resp->json('data'))->firstWhere('slug', $restaurant->slug);
        $this->assertNotNull($item, 'Restaurant not found in list');
        $this->assertNull($item['avg_rating']);
        $this->assertSame(0, $item['review_count']);
    }

    public function test_restaurant_list_avg_rating_and_review_count_reflect_published_reviews(): void
    {
        [$token, $user] = $this->authCustomer();
        [$restaurant, $branch] = $this->freshRestaurant();

        $this->addPublishedReview($restaurant, $branch, $user, 4);
        $this->addPublishedReview($restaurant, $branch, $user, 2);

        $resp = $this->withToken($token)->getJson('/api/mobile/restaurants');
        $resp->assertOk();

        $item = collect($resp->json('data'))->firstWhere('slug', $restaurant->slug);
        $this->assertNotNull($item);
        $this->assertEquals(3.0, $item['avg_rating']); // (4+2)/2 — use assertEquals: JSON drops trailing .0
        $this->assertSame(2, $item['review_count']);
    }

    public function test_restaurant_list_excludes_non_published_reviews_from_aggregates(): void
    {
        [$token, $user] = $this->authCustomer();
        [$restaurant, $branch] = $this->freshRestaurant();

        RestaurantReview::create([
            'restaurant_id' => $restaurant->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'customer_name' => 'Pending',
            'customer_phone' => $user->phone,
            'rating' => 1,
            'status' => RestaurantReview::STATUS_PENDING_REVIEW,
            'source' => RestaurantReview::SOURCE_MOBILE,
        ]);

        $resp = $this->withToken($token)->getJson('/api/mobile/restaurants');
        $resp->assertOk();

        $item = collect($resp->json('data'))->firstWhere('slug', $restaurant->slug);
        $this->assertNotNull($item);
        $this->assertNull($item['avg_rating']);
        $this->assertSame(0, $item['review_count']);
    }

    public function test_restaurant_detail_has_null_avg_rating_and_zero_count_when_no_reviews(): void
    {
        [$token] = $this->authCustomer();
        [$restaurant] = $this->freshRestaurant();

        $resp = $this->withToken($token)->getJson("/api/mobile/restaurants/{$restaurant->slug}");
        $resp->assertOk();

        $resp->assertJsonPath('data.avg_rating', null);
        $resp->assertJsonPath('data.review_count', 0);
    }

    public function test_restaurant_detail_avg_rating_rounds_to_one_decimal(): void
    {
        [$token, $user] = $this->authCustomer();
        [$restaurant, $branch] = $this->freshRestaurant();

        $this->addPublishedReview($restaurant, $branch, $user, 5);
        $this->addPublishedReview($restaurant, $branch, $user, 4);
        $this->addPublishedReview($restaurant, $branch, $user, 4);

        $resp = $this->withToken($token)->getJson("/api/mobile/restaurants/{$restaurant->slug}");
        $resp->assertOk();

        // (5+4+4) / 3 = 4.333… → rounded to 4.3
        $this->assertSame(4.3, $resp->json('data.avg_rating'));
        $this->assertSame(3, $resp->json('data.review_count'));
    }

    public function test_restaurant_detail_excludes_non_published_reviews_from_aggregates(): void
    {
        [$token, $user] = $this->authCustomer();
        [$restaurant, $branch] = $this->freshRestaurant();

        RestaurantReview::create([
            'restaurant_id' => $restaurant->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'customer_name' => 'Hidden',
            'customer_phone' => $user->phone,
            'rating' => 1,
            'status' => RestaurantReview::STATUS_HIDDEN,
            'source' => RestaurantReview::SOURCE_MOBILE,
        ]);

        $resp = $this->withToken($token)->getJson("/api/mobile/restaurants/{$restaurant->slug}");
        $resp->assertOk();

        $resp->assertJsonPath('data.avg_rating', null);
        $resp->assertJsonPath('data.review_count', 0);
    }

    public function test_restaurant_list_structure_includes_rating_fields(): void
    {
        [$token] = $this->authCustomer();

        $resp = $this->withToken($token)->getJson('/api/mobile/restaurants');
        $resp->assertOk();
        $resp->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'slug', 'active_branches_count', 'avg_rating', 'review_count'],
            ],
        ]);
    }

    public function test_restaurant_detail_structure_includes_rating_fields(): void
    {
        [$token] = $this->authCustomer();

        $resp = $this->withToken($token)->getJson('/api/mobile/restaurants/demo-restaurant-a');
        $resp->assertOk();
        $resp->assertJsonStructure([
            'data' => ['id', 'name', 'slug', 'avg_rating', 'review_count', 'branches'],
        ]);
    }
}
