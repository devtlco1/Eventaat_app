<?php

namespace Tests\Feature;

use App\Enums\RestaurantStatus;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantEvent;
use App\Models\RestaurantMenu;
use App\Models\RestaurantMenuCategory;
use App\Models\RestaurantMenuItem;
use App\Models\RestaurantOffer;
use App\Models\RestaurantStory;
use App\Models\User;
use Database\Seeders\RolesAndTestUsersSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MobileRestaurantContentApiTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function authToken(): string
    {
        $user = User::where('email', 'customer@eventaat.test')->firstOrFail();

        return $user->createToken('mobile')->plainTextToken;
    }

    private function makeRestaurant(string $slug = 'test-restaurant', RestaurantStatus $status = RestaurantStatus::Active): Restaurant
    {
        return Restaurant::create([
            'name' => 'Test Restaurant',
            'slug' => $slug,
            'status' => $status,
        ]);
    }

    private function makeBranch(Restaurant $restaurant): Branch
    {
        return Branch::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Main Branch',
            'code' => 'main',
            'status' => 'active',
        ]);
    }

    // ── Menu tests ────────────────────────────────────────────────────────────

    public function test_menus_endpoint_returns_structured_menu_with_categories_and_items(): void
    {
        Storage::fake('public');
        $token = $this->authToken();
        $restaurant = $this->makeRestaurant();

        $menu = RestaurantMenu::create([
            'restaurant_id' => $restaurant->id,
            'title' => 'Lunch Menu',
            'slug' => 'lunch-menu',
            'status' => RestaurantMenu::STATUS_PUBLISHED,
            'menu_mode' => RestaurantMenu::MODE_STRUCTURED,
        ]);

        $category = RestaurantMenuCategory::create([
            'restaurant_menu_id' => $menu->id,
            'name' => 'Starters',
            'display_order' => 0,
            'is_active' => true,
        ]);

        RestaurantMenuItem::create([
            'restaurant_menu_category_id' => $category->id,
            'name' => 'Spring Rolls',
            'description' => 'Crispy veggie rolls',
            'price' => '5.50',
            'currency' => 'IQD',
            'is_available' => true,
            'is_featured' => false,
            'display_order' => 0,
        ]);

        $resp = $this->withToken($token)
            ->getJson('/api/mobile/restaurants/test-restaurant/menus');

        $resp->assertOk();
        $resp->assertJsonPath('data.0.mode', 'structured');
        $resp->assertJsonPath('data.0.title', 'Lunch Menu');
        $resp->assertJsonPath('data.0.categories.0.name', 'Starters');
        $resp->assertJsonPath('data.0.categories.0.items.0.name', 'Spring Rolls');
        $resp->assertJsonPath('data.0.categories.0.items.0.price', '5.50');
        $resp->assertJsonPath('data.0.categories.0.items.0.currency', 'IQD');

        // pdf_url and external_url must be null for structured menus
        $resp->assertJsonPath('data.0.pdf_url', null);
        $resp->assertJsonPath('data.0.external_url', null);
    }

    public function test_menus_endpoint_returns_pdf_url_for_pdf_upload_menu(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('menus/menu.pdf', 'fake-pdf-content');

        $token = $this->authToken();
        $restaurant = $this->makeRestaurant();

        RestaurantMenu::create([
            'restaurant_id' => $restaurant->id,
            'title' => 'PDF Menu',
            'slug' => 'pdf-menu',
            'status' => RestaurantMenu::STATUS_PUBLISHED,
            'menu_mode' => RestaurantMenu::MODE_PDF_UPLOAD,
            'menu_file_path' => 'menus/menu.pdf',
        ]);

        $resp = $this->withToken($token)
            ->getJson('/api/mobile/restaurants/test-restaurant/menus');

        $resp->assertOk();
        $resp->assertJsonPath('data.0.mode', 'pdf_upload');
        $resp->assertJsonCount(0, 'data.0.categories');

        // pdf_url must be a public URL containing the path
        $pdfUrl = $resp->json('data.0.pdf_url');
        $this->assertNotNull($pdfUrl);
        $this->assertStringContainsString('menus/menu.pdf', $pdfUrl);
        $resp->assertJsonPath('data.0.external_url', null);
    }

    public function test_menus_endpoint_returns_external_url_for_external_link_menu(): void
    {
        Storage::fake('public');
        $token = $this->authToken();
        $restaurant = $this->makeRestaurant();

        RestaurantMenu::create([
            'restaurant_id' => $restaurant->id,
            'title' => 'Online Menu',
            'slug' => 'online-menu',
            'status' => RestaurantMenu::STATUS_PUBLISHED,
            'menu_mode' => RestaurantMenu::MODE_EXTERNAL_LINK,
            'menu_url' => 'https://example.com/menu',
        ]);

        $resp = $this->withToken($token)
            ->getJson('/api/mobile/restaurants/test-restaurant/menus');

        $resp->assertOk();
        $resp->assertJsonPath('data.0.mode', 'external_link');
        $resp->assertJsonPath('data.0.external_url', 'https://example.com/menu');
        $resp->assertJsonPath('data.0.pdf_url', null);
        $resp->assertJsonCount(0, 'data.0.categories');
    }

    public function test_unpublished_and_draft_menus_are_hidden(): void
    {
        Storage::fake('public');
        $token = $this->authToken();
        $restaurant = $this->makeRestaurant();

        RestaurantMenu::create([
            'restaurant_id' => $restaurant->id,
            'title' => 'Draft Menu',
            'slug' => 'draft-menu',
            'status' => RestaurantMenu::STATUS_DRAFT,
            'menu_mode' => RestaurantMenu::MODE_STRUCTURED,
        ]);

        RestaurantMenu::create([
            'restaurant_id' => $restaurant->id,
            'title' => 'Archived Menu',
            'slug' => 'archived-menu',
            'status' => RestaurantMenu::STATUS_ARCHIVED,
            'menu_mode' => RestaurantMenu::MODE_STRUCTURED,
        ]);

        $resp = $this->withToken($token)
            ->getJson('/api/mobile/restaurants/test-restaurant/menus');

        $resp->assertOk();
        $resp->assertJsonCount(0, 'data');
    }

    public function test_unavailable_items_and_inactive_categories_are_hidden(): void
    {
        Storage::fake('public');
        $token = $this->authToken();
        $restaurant = $this->makeRestaurant();

        $menu = RestaurantMenu::create([
            'restaurant_id' => $restaurant->id,
            'title' => 'Dinner Menu',
            'slug' => 'dinner-menu',
            'status' => RestaurantMenu::STATUS_PUBLISHED,
            'menu_mode' => RestaurantMenu::MODE_STRUCTURED,
        ]);

        $activeCategory = RestaurantMenuCategory::create([
            'restaurant_menu_id' => $menu->id,
            'name' => 'Mains',
            'display_order' => 0,
            'is_active' => true,
        ]);

        RestaurantMenuCategory::create([
            'restaurant_menu_id' => $menu->id,
            'name' => 'Hidden Section',
            'display_order' => 1,
            'is_active' => false,
        ]);

        RestaurantMenuItem::create([
            'restaurant_menu_category_id' => $activeCategory->id,
            'name' => 'Available Item',
            'price' => '10.00',
            'currency' => 'IQD',
            'is_available' => true,
            'display_order' => 0,
        ]);

        RestaurantMenuItem::create([
            'restaurant_menu_category_id' => $activeCategory->id,
            'name' => 'Unavailable Item',
            'price' => '8.00',
            'currency' => 'IQD',
            'is_available' => false,
            'display_order' => 1,
        ]);

        $resp = $this->withToken($token)
            ->getJson('/api/mobile/restaurants/test-restaurant/menus');

        $resp->assertOk();
        $resp->assertJsonCount(1, 'data.0.categories');
        $resp->assertJsonPath('data.0.categories.0.name', 'Mains');
        $resp->assertJsonCount(1, 'data.0.categories.0.items');
        $resp->assertJsonPath('data.0.categories.0.items.0.name', 'Available Item');
    }

    public function test_menu_item_image_url_is_a_public_storage_url_not_raw_path(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('menus/items/spring-roll.png', 'fake-image');

        $token = $this->authToken();
        $restaurant = $this->makeRestaurant();

        $menu = RestaurantMenu::create([
            'restaurant_id' => $restaurant->id,
            'title' => 'Image Menu',
            'slug' => 'image-menu',
            'status' => RestaurantMenu::STATUS_PUBLISHED,
            'menu_mode' => RestaurantMenu::MODE_STRUCTURED,
        ]);

        $category = RestaurantMenuCategory::create([
            'restaurant_menu_id' => $menu->id,
            'name' => 'Category',
            'display_order' => 0,
            'is_active' => true,
        ]);

        RestaurantMenuItem::create([
            'restaurant_menu_category_id' => $category->id,
            'name' => 'Item with Image',
            'price' => '5.00',
            'currency' => 'IQD',
            'is_available' => true,
            'image_path' => 'menus/items/spring-roll.png',
            'display_order' => 0,
        ]);

        $resp = $this->withToken($token)
            ->getJson('/api/mobile/restaurants/test-restaurant/menus');

        $resp->assertOk();
        $imageUrl = $resp->json('data.0.categories.0.items.0.image_url');
        $this->assertNotNull($imageUrl);
        // Must include the file path and be a rooted URL (not the raw relative storage path alone).
        $this->assertStringContainsString('menus/items/spring-roll.png', $imageUrl);
        // Must NOT be the bare relative disk path — Storage::url() always prepends a prefix.
        $this->assertNotEquals('menus/items/spring-roll.png', $imageUrl);
        $this->assertStringStartsWith('/', $imageUrl);
    }

    // ── Offer tests ───────────────────────────────────────────────────────────

    public function test_offers_endpoint_returns_only_published_offers(): void
    {
        $token = $this->authToken();
        $restaurant = $this->makeRestaurant();

        RestaurantOffer::create([
            'restaurant_id' => $restaurant->id,
            'title' => 'Published Offer',
            'slug' => 'published-offer',
            'status' => RestaurantOffer::STATUS_PUBLISHED,
            'offer_type' => RestaurantOffer::TYPE_TEXT_ONLY,
        ]);

        RestaurantOffer::create([
            'restaurant_id' => $restaurant->id,
            'title' => 'Draft Offer',
            'slug' => 'draft-offer',
            'status' => RestaurantOffer::STATUS_DRAFT,
            'offer_type' => RestaurantOffer::TYPE_TEXT_ONLY,
        ]);

        RestaurantOffer::create([
            'restaurant_id' => $restaurant->id,
            'title' => 'Pending Offer',
            'slug' => 'pending-offer',
            'status' => RestaurantOffer::STATUS_PENDING_REVIEW,
            'offer_type' => RestaurantOffer::TYPE_TEXT_ONLY,
        ]);

        $resp = $this->withToken($token)
            ->getJson('/api/mobile/restaurants/test-restaurant/offers');

        $resp->assertOk();
        $resp->assertJsonCount(1, 'data');
        $resp->assertJsonPath('data.0.title', 'Published Offer');
    }

    public function test_expired_offers_by_date_are_hidden(): void
    {
        $token = $this->authToken();
        $restaurant = $this->makeRestaurant();

        RestaurantOffer::create([
            'restaurant_id' => $restaurant->id,
            'title' => 'Expired Offer',
            'slug' => 'expired-offer',
            'status' => RestaurantOffer::STATUS_PUBLISHED,
            'offer_type' => RestaurantOffer::TYPE_TEXT_ONLY,
            'ends_at' => Carbon::now()->subHour(),
        ]);

        RestaurantOffer::create([
            'restaurant_id' => $restaurant->id,
            'title' => 'Active Offer',
            'slug' => 'active-offer',
            'status' => RestaurantOffer::STATUS_PUBLISHED,
            'offer_type' => RestaurantOffer::TYPE_TEXT_ONLY,
            'ends_at' => Carbon::now()->addDay(),
        ]);

        $resp = $this->withToken($token)
            ->getJson('/api/mobile/restaurants/test-restaurant/offers');

        $resp->assertOk();
        $resp->assertJsonCount(1, 'data');
        $resp->assertJsonPath('data.0.title', 'Active Offer');
    }

    public function test_offer_response_does_not_expose_admin_fields(): void
    {
        $token = $this->authToken();
        $restaurant = $this->makeRestaurant();

        RestaurantOffer::create([
            'restaurant_id' => $restaurant->id,
            'title' => 'Test Offer',
            'slug' => 'test-offer',
            'status' => RestaurantOffer::STATUS_PUBLISHED,
            'offer_type' => RestaurantOffer::TYPE_TEXT_ONLY,
            'notes' => 'Internal note that must NOT appear in API',
        ]);

        $resp = $this->withToken($token)
            ->getJson('/api/mobile/restaurants/test-restaurant/offers');

        $resp->assertOk();
        $resp->assertJsonMissingPath('data.0.notes');
        $resp->assertJsonMissingPath('data.0.slug');
    }

    // ── Story tests ───────────────────────────────────────────────────────────

    public function test_stories_endpoint_returns_only_published_stories(): void
    {
        $token = $this->authToken();
        $restaurant = $this->makeRestaurant();

        RestaurantStory::create([
            'restaurant_id' => $restaurant->id,
            'title' => 'Published Story',
            'slug' => 'published-story',
            'status' => RestaurantStory::STATUS_PUBLISHED,
            'story_type' => RestaurantStory::TYPE_TEXT,
            'body' => 'Story body text',
            'lifetime_mode' => RestaurantStory::LIFETIME_MANUAL,
        ]);

        RestaurantStory::create([
            'restaurant_id' => $restaurant->id,
            'title' => 'Draft Story',
            'slug' => 'draft-story',
            'status' => RestaurantStory::STATUS_DRAFT,
            'story_type' => RestaurantStory::TYPE_TEXT,
            'body' => 'Draft text',
            'lifetime_mode' => RestaurantStory::LIFETIME_MANUAL,
        ]);

        $resp = $this->withToken($token)
            ->getJson('/api/mobile/restaurants/test-restaurant/stories');

        $resp->assertOk();
        $resp->assertJsonCount(1, 'data');
        $resp->assertJsonPath('data.0.title', 'Published Story');
    }

    public function test_expired_stories_by_date_are_hidden(): void
    {
        $token = $this->authToken();
        $restaurant = $this->makeRestaurant();

        RestaurantStory::create([
            'restaurant_id' => $restaurant->id,
            'title' => 'Expired Story',
            'slug' => 'expired-story',
            'status' => RestaurantStory::STATUS_PUBLISHED,
            'story_type' => RestaurantStory::TYPE_TEXT,
            'body' => 'Old story',
            'lifetime_mode' => RestaurantStory::LIFETIME_MANUAL,
            'ends_at' => Carbon::now()->subHour(),
        ]);

        RestaurantStory::create([
            'restaurant_id' => $restaurant->id,
            'title' => 'Live Story',
            'slug' => 'live-story',
            'status' => RestaurantStory::STATUS_PUBLISHED,
            'story_type' => RestaurantStory::TYPE_TEXT,
            'body' => 'Live story',
            'lifetime_mode' => RestaurantStory::LIFETIME_MANUAL,
        ]);

        $resp = $this->withToken($token)
            ->getJson('/api/mobile/restaurants/test-restaurant/stories');

        $resp->assertOk();
        $resp->assertJsonCount(1, 'data');
        $resp->assertJsonPath('data.0.title', 'Live Story');
    }

    public function test_story_response_does_not_expose_admin_fields(): void
    {
        $token = $this->authToken();
        $restaurant = $this->makeRestaurant();

        RestaurantStory::create([
            'restaurant_id' => $restaurant->id,
            'title' => 'Test Story',
            'slug' => 'test-story',
            'status' => RestaurantStory::STATUS_PUBLISHED,
            'story_type' => RestaurantStory::TYPE_TEXT,
            'body' => 'Story text',
            'lifetime_mode' => RestaurantStory::LIFETIME_MANUAL,
            'notes' => 'Admin note must NOT appear',
        ]);

        $resp = $this->withToken($token)
            ->getJson('/api/mobile/restaurants/test-restaurant/stories');

        $resp->assertOk();
        $resp->assertJsonMissingPath('data.0.notes');
        $resp->assertJsonMissingPath('data.0.slug');
        $resp->assertJsonMissingPath('data.0.lifetime_mode');
        $resp->assertJsonMissingPath('data.0.lifetime_hours');
    }

    // ── Event nights tests ────────────────────────────────────────────────────

    public function test_event_nights_endpoint_returns_only_published_upcoming_events(): void
    {
        $token = $this->authToken();
        $restaurant = $this->makeRestaurant();

        // Published future event — must appear
        RestaurantEvent::create([
            'restaurant_id' => $restaurant->id,
            'title' => 'Upcoming Event',
            'slug' => 'upcoming-event',
            'status' => RestaurantEvent::STATUS_PUBLISHED,
            'booking_mode' => RestaurantEvent::BOOKING_MODE_INFO_ONLY,
            'starts_at' => Carbon::now()->addDay(),
        ]);

        // Draft event — must NOT appear
        RestaurantEvent::create([
            'restaurant_id' => $restaurant->id,
            'title' => 'Draft Event',
            'slug' => 'draft-event',
            'status' => RestaurantEvent::STATUS_DRAFT,
            'booking_mode' => RestaurantEvent::BOOKING_MODE_INFO_ONLY,
            'starts_at' => Carbon::now()->addDay(),
        ]);

        // Published past event — must NOT appear
        RestaurantEvent::create([
            'restaurant_id' => $restaurant->id,
            'title' => 'Past Event',
            'slug' => 'past-event',
            'status' => RestaurantEvent::STATUS_PUBLISHED,
            'booking_mode' => RestaurantEvent::BOOKING_MODE_INFO_ONLY,
            'starts_at' => Carbon::now()->subDay(),
        ]);

        $resp = $this->withToken($token)
            ->getJson('/api/mobile/restaurants/test-restaurant/event-nights');

        $resp->assertOk();
        $resp->assertJsonCount(1, 'data');
        $resp->assertJsonPath('data.0.title', 'Upcoming Event');
    }

    public function test_event_night_includes_capacity_summary(): void
    {
        $token = $this->authToken();
        $restaurant = $this->makeRestaurant();

        RestaurantEvent::create([
            'restaurant_id' => $restaurant->id,
            'title' => 'Capacity Event',
            'slug' => 'capacity-event',
            'status' => RestaurantEvent::STATUS_PUBLISHED,
            'booking_mode' => RestaurantEvent::BOOKING_MODE_NORMAL,
            'starts_at' => Carbon::now()->addDays(2),
            'capacity' => 50,
        ]);

        $resp = $this->withToken($token)
            ->getJson('/api/mobile/restaurants/test-restaurant/event-nights');

        $resp->assertOk();
        $resp->assertJsonPath('data.0.capacity', 50);
        $resp->assertJsonPath('data.0.active_reserved_seats', 0);
        $resp->assertJsonPath('data.0.remaining_seats', 50);
    }

    public function test_event_night_response_does_not_expose_admin_fields(): void
    {
        $token = $this->authToken();
        $restaurant = $this->makeRestaurant();

        RestaurantEvent::create([
            'restaurant_id' => $restaurant->id,
            'title' => 'Test Event',
            'slug' => 'test-event-admin',
            'status' => RestaurantEvent::STATUS_PUBLISHED,
            'booking_mode' => RestaurantEvent::BOOKING_MODE_INFO_ONLY,
            'starts_at' => Carbon::now()->addDay(),
            'notes' => 'Admin note must NOT appear',
        ]);

        $resp = $this->withToken($token)
            ->getJson('/api/mobile/restaurants/test-restaurant/event-nights');

        $resp->assertOk();
        $resp->assertJsonMissingPath('data.0.notes');
    }

    // ── Inactive restaurant returns 404 ───────────────────────────────────────

    public function test_inactive_restaurant_slug_returns_404_for_all_content_endpoints(): void
    {
        $token = $this->authToken();
        $this->makeRestaurant('inactive-restaurant', RestaurantStatus::Inactive);

        $this->withToken($token)
            ->getJson('/api/mobile/restaurants/inactive-restaurant/menus')
            ->assertNotFound();

        $this->withToken($token)
            ->getJson('/api/mobile/restaurants/inactive-restaurant/offers')
            ->assertNotFound();

        $this->withToken($token)
            ->getJson('/api/mobile/restaurants/inactive-restaurant/stories')
            ->assertNotFound();

        $this->withToken($token)
            ->getJson('/api/mobile/restaurants/inactive-restaurant/event-nights')
            ->assertNotFound();
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->makeRestaurant();

        $this->getJson('/api/mobile/restaurants/test-restaurant/menus')->assertUnauthorized();
        $this->getJson('/api/mobile/restaurants/test-restaurant/offers')->assertUnauthorized();
        $this->getJson('/api/mobile/restaurants/test-restaurant/stories')->assertUnauthorized();
        $this->getJson('/api/mobile/restaurants/test-restaurant/event-nights')->assertUnauthorized();
    }
}
