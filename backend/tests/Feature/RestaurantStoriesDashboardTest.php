<?php

namespace Tests\Feature;

use App\Enums\BranchStatus;
use App\Enums\RestaurantStaffRole;
use App\Enums\RestaurantStatus;
use App\Filament\Platform\Resources\RestaurantStories\Pages\CreateRestaurantStory as PlatformCreateRestaurantStory;
use App\Filament\Restaurant\Resources\RestaurantStories\Pages\CreateRestaurantStory as RestaurantCreateRestaurantStory;
use App\Filament\Restaurant\Resources\RestaurantStories\Pages\ListRestaurantStories;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantStaffAssignment;
use App\Models\RestaurantStory;
use App\Models\RestaurantStoryItem;
use App\Models\User;
use Database\Seeders\RolesAndTestUsersSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class RestaurantStoriesDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
    }

    private function seedRestaurantsAndStories(): array
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

        $aRestaurantWide = RestaurantStory::create([
            'restaurant_id' => $a->id,
            'branch_id' => null,
            'title' => 'A Wide',
            'slug' => 'a-wide-story',
            'story_type' => RestaurantStory::TYPE_TEXT,
            'body' => 'Hello',
            'status' => RestaurantStory::STATUS_PUBLISHED,
            'display_order' => 0,
        ]);

        $aBranchStory = RestaurantStory::create([
            'restaurant_id' => $a->id,
            'branch_id' => $aBranch->id,
            'title' => 'A Branch',
            'slug' => 'a-branch-story',
            'story_type' => RestaurantStory::TYPE_IMAGE,
            'media_url' => 'https://example.com/a.jpg',
            'status' => RestaurantStory::STATUS_PUBLISHED,
            'display_order' => 0,
        ]);

        $bBranchStory = RestaurantStory::create([
            'restaurant_id' => $b->id,
            'branch_id' => $bBranch->id,
            'title' => 'B Branch',
            'slug' => 'b-branch-story',
            'story_type' => RestaurantStory::TYPE_TEXT,
            'body' => 'B',
            'status' => RestaurantStory::STATUS_PUBLISHED,
            'display_order' => 0,
        ]);

        return compact('a', 'b', 'aBranch', 'bBranch', 'aRestaurantWide', 'aBranchStory', 'bBranchStory');
    }

    private function assertDeniedOrNotFound(int $status): void
    {
        $this->assertTrue(in_array($status, [403, 404], true), "Expected 403/404, got {$status}");
    }

    public function test_platform_can_list_and_access_create_page(): void
    {
        $data = $this->seedRestaurantsAndStories();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        $index = $this->get('/platform/restaurant-stories');
        $index->assertOk();
        $index->assertSee($data['aRestaurantWide']->title);
        $index->assertSee($data['aBranchStory']->title);
        $index->assertSee($data['bBranchStory']->title);

        $this->get('/platform/restaurant-stories/create')->assertOk();
    }

    public function test_platform_can_create_text_story(): void
    {
        $data = $this->seedRestaurantsAndStories();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        $itemKey = (string) Str::uuid();

        Livewire::test(PlatformCreateRestaurantStory::class)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', null)
            ->set('data.title', 'New text')
            ->set('data.slug', 'new-text')
            ->set('data.status', RestaurantStory::STATUS_DRAFT)
            ->set('data.display_order', 0)
            ->set('data.items.'.$itemKey.'.item_type', RestaurantStoryItem::TYPE_TEXT)
            ->set('data.items.'.$itemKey.'.body', 'Body')
            ->set('data.items.'.$itemKey.'.sort_order', 0)
            ->call('create')
            ->assertHasNoErrors();

        $story = RestaurantStory::query()->where('slug', 'new-text')->firstOrFail();

        $this->assertDatabaseHas('restaurant_stories', [
            'slug' => 'new-text',
        ]);

        $this->assertDatabaseHas('restaurant_story_items', [
            'restaurant_story_id' => $story->id,
            'item_type' => RestaurantStoryItem::TYPE_TEXT,
        ]);
    }

    public function test_platform_can_create_story_with_image_item_in_same_form(): void
    {
        Storage::fake('public');

        $data = $this->seedRestaurantsAndStories();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        $itemKey = (string) Str::uuid();
        $upload = UploadedFile::fake()->image('story-slide.jpg');

        Livewire::test(PlatformCreateRestaurantStory::class)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', null)
            ->set('data.title', 'Image story')
            ->set('data.slug', 'image-slide-story')
            ->set('data.status', RestaurantStory::STATUS_DRAFT)
            ->set('data.display_order', 0)
            ->set('data.items.'.$itemKey.'.item_type', RestaurantStoryItem::TYPE_IMAGE)
            ->set('data.items.'.$itemKey.'.media_path', [$upload])
            ->set('data.items.'.$itemKey.'.sort_order', 0)
            ->call('create')
            ->assertHasNoErrors();

        $story = RestaurantStory::query()->where('slug', 'image-slide-story')->firstOrFail();
        $path = $story->items()->value('media_path');
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_platform_can_create_story_with_video_item_in_same_form(): void
    {
        Storage::fake('public');

        $data = $this->seedRestaurantsAndStories();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        $itemKey = (string) Str::uuid();
        // Keep uploads minimal: Livewire serializes temp uploads across requests; large fakes can exhaust PHP memory.
        $upload = UploadedFile::fake()->create('story-slide.mp4', 1, 'video/mp4');

        Livewire::test(PlatformCreateRestaurantStory::class)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', null)
            ->set('data.title', 'Video story')
            ->set('data.slug', 'video-slide-story')
            ->set('data.status', RestaurantStory::STATUS_DRAFT)
            ->set('data.display_order', 0)
            ->set('data.items.'.$itemKey.'.item_type', RestaurantStoryItem::TYPE_VIDEO)
            ->set('data.items.'.$itemKey.'.media_path', [$upload])
            ->set('data.items.'.$itemKey.'.sort_order', 0)
            ->call('create')
            ->assertHasNoErrors();

        $story = RestaurantStory::query()->where('slug', 'video-slide-story')->firstOrFail();
        $path = $story->items()->value('media_path');
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_platform_create_rejects_incomplete_image_slide(): void
    {
        $data = $this->seedRestaurantsAndStories();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        $itemKey = (string) Str::uuid();

        Livewire::test(PlatformCreateRestaurantStory::class)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', null)
            ->set('data.title', 'Bad image slide')
            ->set('data.slug', 'bad-image-slide-story')
            ->set('data.status', RestaurantStory::STATUS_DRAFT)
            ->set('data.display_order', 0)
            ->set('data.items.'.$itemKey.'.item_type', RestaurantStoryItem::TYPE_IMAGE)
            ->set('data.items.'.$itemKey.'.sort_order', 0)
            ->call('create')
            ->assertHasErrors();
    }

    public function test_platform_pending_review_requires_at_least_one_slide(): void
    {
        $data = $this->seedRestaurantsAndStories();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformCreateRestaurantStory::class)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', null)
            ->set('data.title', 'No slides')
            ->set('data.slug', 'no-slides-pending-story')
            ->set('data.status', RestaurantStory::STATUS_PENDING_REVIEW)
            ->set('data.display_order', 0)
            ->call('create')
            ->assertHasErrors(['data.items']);
    }

    public function test_restaurant_owner_sees_only_assigned_restaurant_stories_including_restaurant_wide(): void
    {
        $data = $this->seedRestaurantsAndStories();

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

        $index = $this->get('/restaurant/restaurant-stories');
        $index->assertOk();
        $index->assertSee($data['aRestaurantWide']->title);
        $index->assertSee($data['aBranchStory']->title);
        $index->assertDontSee($data['bBranchStory']->title);

        $this->get("/restaurant/restaurant-stories/{$data['bBranchStory']->id}")
            ->tap(fn ($resp) => $this->assertDeniedOrNotFound($resp->getStatusCode()));
    }

    public function test_branch_manager_sees_only_branch_scoped_stories_and_cannot_access_restaurant_wide_story(): void
    {
        $data = $this->seedRestaurantsAndStories();

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

        $index = $this->get('/restaurant/restaurant-stories');
        $index->assertOk();
        $index->assertSee($data['aBranchStory']->title);
        $index->assertDontSee($data['aRestaurantWide']->title);
        $index->assertDontSee($data['bBranchStory']->title);

        $this->get("/restaurant/restaurant-stories/{$data['aRestaurantWide']->id}")
            ->tap(fn ($resp) => $this->assertDeniedOrNotFound($resp->getStatusCode()));

        $this->get("/restaurant/restaurant-stories/{$data['aBranchStory']->id}/edit")->assertOk();
    }

    public function test_story_has_many_items_relationship(): void
    {
        $data = $this->seedRestaurantsAndStories();

        RestaurantStoryItem::create([
            'restaurant_story_id' => $data['aBranchStory']->id,
            'item_type' => RestaurantStoryItem::TYPE_TEXT,
            'body' => 'Slide',
            'sort_order' => 1,
        ]);

        $data['aBranchStory']->refresh();

        $this->assertCount(1, $data['aBranchStory']->items);
        $this->assertSame(RestaurantStoryItem::TYPE_TEXT, $data['aBranchStory']->items()->first()->item_type);
    }

    public function test_apply_lifetime_window_modes(): void
    {
        try {
            Carbon::setTestNow(Carbon::parse('2026-06-02 09:00:00', 'UTC'));

            $s12 = new RestaurantStory([
                'lifetime_mode' => RestaurantStory::LIFETIME_12H,
                'starts_at' => null,
                'ends_at' => null,
            ]);
            $s12->applyLifetimeWindowForPublishing();
            $this->assertNotNull($s12->starts_at);
            $this->assertTrue($s12->ends_at->equalTo($s12->starts_at->copy()->addHours(12)));

            $s48 = new RestaurantStory([
                'lifetime_mode' => RestaurantStory::LIFETIME_48H,
                'starts_at' => Carbon::parse('2026-06-02 08:00:00', 'UTC'),
                'ends_at' => null,
            ]);
            $s48->applyLifetimeWindowForPublishing();
            $this->assertTrue($s48->ends_at->equalTo($s48->starts_at->copy()->addHours(48)));

            $manual = new RestaurantStory([
                'lifetime_mode' => RestaurantStory::LIFETIME_MANUAL,
                'starts_at' => Carbon::parse('2026-06-02 08:00:00', 'UTC'),
                'ends_at' => null,
            ]);
            $manual->applyLifetimeWindowForPublishing();
            $this->assertNull($manual->ends_at);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_branch_must_belong_to_selected_restaurant_validation(): void
    {
        $data = $this->seedRestaurantsAndStories();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformCreateRestaurantStory::class)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', $data['bBranch']->id)
            ->set('data.title', 'Bad Branch')
            ->set('data.slug', 'bad-branch-story')
            ->set('data.status', RestaurantStory::STATUS_DRAFT)
            ->set('data.display_order', 0)
            ->call('create')
            ->assertHasErrors(['data.branch_id']);
    }

    public function test_ends_at_must_be_after_starts_at(): void
    {
        $data = $this->seedRestaurantsAndStories();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformCreateRestaurantStory::class)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', null)
            ->set('data.title', 'Bad Dates')
            ->set('data.slug', 'bad-dates-story')
            ->set('data.status', RestaurantStory::STATUS_DRAFT)
            ->set('data.starts_at', '2026-06-04 12:00:00')
            ->set('data.ends_at', '2026-06-04 11:00:00')
            ->set('data.display_order', 0)
            ->call('create')
            ->assertHasErrors(['data.ends_at']);
    }

    public function test_platform_cannot_publish_story_without_items_or_legacy_content(): void
    {
        $data = $this->seedRestaurantsAndStories();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformCreateRestaurantStory::class)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', null)
            ->set('data.title', 'No content')
            ->set('data.slug', 'no-content-story')
            ->set('data.status', RestaurantStory::STATUS_PUBLISHED)
            ->set('data.display_order', 0)
            ->call('create')
            ->assertHasErrors(['data.items']);
    }

    public function test_story_item_requires_media_for_image_items(): void
    {
        $this->expectException(ValidationException::class);

        $data = $this->seedRestaurantsAndStories();

        $story = RestaurantStory::create([
            'restaurant_id' => $data['a']->id,
            'branch_id' => null,
            'title' => 'Item validation',
            'slug' => 'item-validation-story',
            'story_type' => RestaurantStory::TYPE_IMAGE,
            'media_url' => null,
            'body' => null,
            'status' => RestaurantStory::STATUS_DRAFT,
            'display_order' => 0,
        ]);

        RestaurantStoryItem::create([
            'restaurant_story_id' => $story->id,
            'item_type' => RestaurantStoryItem::TYPE_IMAGE,
            'media_path' => null,
            'sort_order' => 0,
        ]);
    }

    public function test_story_item_requires_body_for_text_items(): void
    {
        $this->expectException(ValidationException::class);

        $data = $this->seedRestaurantsAndStories();

        $story = RestaurantStory::create([
            'restaurant_id' => $data['a']->id,
            'branch_id' => null,
            'title' => 'Item validation text',
            'slug' => 'item-validation-text-story',
            'story_type' => RestaurantStory::TYPE_TEXT,
            'media_url' => null,
            'body' => null,
            'status' => RestaurantStory::STATUS_DRAFT,
            'display_order' => 0,
        ]);

        RestaurantStoryItem::create([
            'restaurant_story_id' => $story->id,
            'item_type' => RestaurantStoryItem::TYPE_TEXT,
            'body' => null,
            'sort_order' => 0,
        ]);
    }

    public function test_restaurant_user_can_submit_draft_for_review_and_cannot_publish_directly(): void
    {
        $data = $this->seedRestaurantsAndStories();

        $owner = User::where('email', 'restaurant_owner@eventaat.test')->firstOrFail();
        RestaurantStaffAssignment::create([
            'user_id' => $owner->id,
            'restaurant_id' => $data['a']->id,
            'branch_id' => null,
            'role' => RestaurantStaffRole::RestaurantOwner,
            'status' => 'active',
        ]);

        $ownerForPanel = User::query()->findOrFail($owner->id);
        Filament::setCurrentPanel('restaurant');
        Livewire::actingAs($ownerForPanel, 'web');

        Livewire::test(RestaurantCreateRestaurantStory::class)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', null)
            ->set('data.title', 'Try publish')
            ->set('data.slug', 'try-publish-story')
            ->set('data.status', RestaurantStory::STATUS_PUBLISHED)
            ->set('data.display_order', 0)
            ->call('create')
            ->assertHasErrors(['data.status']);

        $itemKey = (string) Str::uuid();

        Livewire::test(RestaurantCreateRestaurantStory::class)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', null)
            ->set('data.title', 'Draft')
            ->set('data.slug', 'draft-story')
            ->set('data.status', RestaurantStory::STATUS_DRAFT)
            ->set('data.display_order', 0)
            ->set('data.items.'.$itemKey.'.item_type', RestaurantStoryItem::TYPE_TEXT)
            ->set('data.items.'.$itemKey.'.body', 'Slide')
            ->set('data.items.'.$itemKey.'.sort_order', 0)
            ->call('create')
            ->assertHasNoErrors();

        $draft = RestaurantStory::query()->where('slug', 'draft-story')->firstOrFail();

        Livewire::test(ListRestaurantStories::class)
            ->callTableAction('submit_for_review', $draft);

        $this->assertDatabaseHas('restaurant_stories', [
            'id' => $draft->id,
            'status' => RestaurantStory::STATUS_PENDING_REVIEW,
        ]);
    }

    public function test_platform_can_approve_reject_cancel(): void
    {
        $data = $this->seedRestaurantsAndStories();

        $pending = RestaurantStory::create([
            'restaurant_id' => $data['a']->id,
            'branch_id' => $data['aBranch']->id,
            'title' => 'Pending',
            'slug' => 'pending-story',
            'story_type' => RestaurantStory::TYPE_TEXT,
            'body' => 'X',
            'status' => RestaurantStory::STATUS_PENDING_REVIEW,
            'display_order' => 0,
        ]);

        RestaurantStoryItem::create([
            'restaurant_story_id' => $pending->id,
            'item_type' => RestaurantStoryItem::TYPE_TEXT,
            'body' => 'Slide',
            'sort_order' => 0,
        ]);

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        try {
            Carbon::setTestNow(Carbon::parse('2026-06-01 10:00:00', 'UTC'));

            Livewire::test(\App\Filament\Platform\Resources\RestaurantStories\Pages\ListRestaurantStories::class)
                ->callTableAction('approve', $pending);

            $pending->refresh();

            $this->assertDatabaseHas('restaurant_stories', [
                'id' => $pending->id,
                'status' => RestaurantStory::STATUS_PUBLISHED,
            ]);

            $this->assertNotNull($pending->starts_at);
            $this->assertNotNull($pending->ends_at);
            $this->assertTrue($pending->ends_at->equalTo($pending->starts_at->copy()->addHours(24)));
        } finally {
            Carbon::setTestNow();
        }

        $pending2 = RestaurantStory::create([
            'restaurant_id' => $data['a']->id,
            'branch_id' => $data['aBranch']->id,
            'title' => 'Pending2',
            'slug' => 'pending-story-2',
            'story_type' => RestaurantStory::TYPE_TEXT,
            'body' => 'X',
            'status' => RestaurantStory::STATUS_PENDING_REVIEW,
            'display_order' => 0,
        ]);

        RestaurantStoryItem::create([
            'restaurant_story_id' => $pending2->id,
            'item_type' => RestaurantStoryItem::TYPE_TEXT,
            'body' => 'Slide',
            'sort_order' => 0,
        ]);

        Livewire::test(\App\Filament\Platform\Resources\RestaurantStories\Pages\ListRestaurantStories::class)
            ->callTableAction('reject', $pending2);

        $this->assertDatabaseHas('restaurant_stories', [
            'id' => $pending2->id,
            'status' => RestaurantStory::STATUS_REJECTED,
        ]);

        Livewire::test(\App\Filament\Platform\Resources\RestaurantStories\Pages\ListRestaurantStories::class)
            ->callTableAction('cancel', $pending2);

        $this->assertDatabaseHas('restaurant_stories', [
            'id' => $pending2->id,
            'status' => RestaurantStory::STATUS_CANCELLED,
        ]);
    }

    public function test_stories_expire_command_expires_only_old_published_stories(): void
    {
        $data = $this->seedRestaurantsAndStories();

        try {
            Carbon::setTestNow(Carbon::parse('2026-06-10 12:00:00', 'UTC'));

            $publishedOld = RestaurantStory::create([
                'restaurant_id' => $data['a']->id,
                'branch_id' => $data['aBranch']->id,
                'title' => 'Old published',
                'slug' => 'old-published-story',
                'story_type' => RestaurantStory::TYPE_TEXT,
                'body' => 'X',
                'status' => RestaurantStory::STATUS_PUBLISHED,
                'ends_at' => Carbon::now()->subDay(),
                'display_order' => 0,
            ]);

            $draftOld = RestaurantStory::create([
                'restaurant_id' => $data['a']->id,
                'branch_id' => $data['aBranch']->id,
                'title' => 'Old draft',
                'slug' => 'old-draft-story',
                'story_type' => RestaurantStory::TYPE_TEXT,
                'body' => 'X',
                'status' => RestaurantStory::STATUS_DRAFT,
                'ends_at' => Carbon::now()->subDay(),
                'display_order' => 0,
            ]);

            $this->artisan('stories:expire')->assertExitCode(0);

            $this->assertDatabaseHas('restaurant_stories', [
                'id' => $publishedOld->id,
                'status' => RestaurantStory::STATUS_EXPIRED,
            ]);

            $this->assertDatabaseHas('restaurant_stories', [
                'id' => $draftOld->id,
                'status' => RestaurantStory::STATUS_DRAFT,
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }
}
