<?php

namespace Tests\Feature;

use App\Enums\BranchStatus;
use App\Enums\RestaurantStaffRole;
use App\Enums\RestaurantStatus;
use App\Filament\Platform\Resources\RestaurantStories\Pages\CreateRestaurantStory as PlatformCreateRestaurantStory;
use App\Filament\Restaurant\Resources\RestaurantStories\Pages\CreateRestaurantStory as RestaurantCreateRestaurantStory;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantStaffAssignment;
use App\Models\RestaurantStory;
use App\Models\User;
use Database\Seeders\RolesAndTestUsersSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

        Livewire::test(PlatformCreateRestaurantStory::class)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', null)
            ->set('data.title', 'New text')
            ->set('data.slug', 'new-text')
            ->set('data.story_type', RestaurantStory::TYPE_TEXT)
            ->set('data.body', 'Body')
            ->set('data.status', RestaurantStory::STATUS_DRAFT)
            ->set('data.display_order', 0)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('restaurant_stories', [
            'slug' => 'new-text',
            'story_type' => RestaurantStory::TYPE_TEXT,
        ]);
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
            ->set('data.story_type', RestaurantStory::TYPE_TEXT)
            ->set('data.body', 'X')
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
            ->set('data.story_type', RestaurantStory::TYPE_TEXT)
            ->set('data.body', 'X')
            ->set('data.status', RestaurantStory::STATUS_DRAFT)
            ->set('data.starts_at', '2026-06-04 12:00:00')
            ->set('data.ends_at', '2026-06-04 11:00:00')
            ->set('data.display_order', 0)
            ->call('create')
            ->assertHasErrors(['data.ends_at']);
    }

    public function test_image_story_requires_media_url(): void
    {
        $data = $this->seedRestaurantsAndStories();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformCreateRestaurantStory::class)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', null)
            ->set('data.title', 'Image missing url')
            ->set('data.slug', 'image-missing-url')
            ->set('data.story_type', RestaurantStory::TYPE_IMAGE)
            ->set('data.media_url', null)
            ->set('data.status', RestaurantStory::STATUS_DRAFT)
            ->set('data.display_order', 0)
            ->call('create')
            ->assertHasErrors(['data.media_url']);
    }

    public function test_text_story_requires_body(): void
    {
        $data = $this->seedRestaurantsAndStories();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformCreateRestaurantStory::class)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', null)
            ->set('data.title', 'Text missing body')
            ->set('data.slug', 'text-missing-body')
            ->set('data.story_type', RestaurantStory::TYPE_TEXT)
            ->set('data.body', null)
            ->set('data.status', RestaurantStory::STATUS_DRAFT)
            ->set('data.display_order', 0)
            ->call('create')
            ->assertHasErrors(['data.body']);
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
            ->set('data.story_type', RestaurantStory::TYPE_TEXT)
            ->set('data.body', 'X')
            ->set('data.status', RestaurantStory::STATUS_PUBLISHED)
            ->set('data.display_order', 0)
            ->call('create')
            ->assertHasErrors(['data.status']);

        $draft = RestaurantStory::create([
            'restaurant_id' => $data['a']->id,
            'branch_id' => null,
            'title' => 'Draft',
            'slug' => 'draft-story',
            'story_type' => RestaurantStory::TYPE_TEXT,
            'body' => 'X',
            'status' => RestaurantStory::STATUS_DRAFT,
            'display_order' => 0,
        ]);

        Livewire::test(\App\Filament\Restaurant\Resources\RestaurantStories\Pages\ListRestaurantStories::class)
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

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(\App\Filament\Platform\Resources\RestaurantStories\Pages\ListRestaurantStories::class)
            ->callTableAction('approve', $pending);

        $this->assertDatabaseHas('restaurant_stories', [
            'id' => $pending->id,
            'status' => RestaurantStory::STATUS_PUBLISHED,
        ]);

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
    }
}

