<?php

namespace Tests\Feature;

use App\Enums\BranchStatus;
use App\Enums\RestaurantStaffRole;
use App\Enums\RestaurantStatus;
use App\Filament\Platform\Resources\RestaurantMenus\Pages\CreateRestaurantMenu as PlatformCreateRestaurantMenu;
use App\Filament\Platform\Resources\RestaurantMenus\Pages\EditRestaurantMenu as PlatformEditRestaurantMenu;
use App\Filament\Platform\Resources\RestaurantMenus\Pages\ViewRestaurantMenu as PlatformViewRestaurantMenu;
use App\Livewire\Filament\RestaurantMenuStructuredContent;
use App\Livewire\Filament\RestaurantMenuStructuredItemsTable;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantMenu;
use App\Models\RestaurantMenuCategory;
use App\Models\RestaurantMenuItem;
use App\Models\RestaurantStaffAssignment;
use App\Models\User;
use Database\Seeders\RolesAndTestUsersSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class RestaurantMenusDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function seedRestaurantsAndMenus(): array
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

        $aWide = RestaurantMenu::create([
            'restaurant_id' => $a->id,
            'branch_id' => null,
            'title' => 'A Wide Menu',
            'slug' => 'a-wide-menu',
            'status' => RestaurantMenu::STATUS_PUBLISHED,
            'menu_mode' => RestaurantMenu::MODE_STRUCTURED,
            'menu_file_path' => null,
            'menu_url' => null,
            'description' => null,
            'notes' => null,
            'display_order' => 0,
        ]);

        $aBranchMenu = RestaurantMenu::create([
            'restaurant_id' => $a->id,
            'branch_id' => $aBranch->id,
            'title' => 'A Branch Menu',
            'slug' => 'a-branch-menu',
            'status' => RestaurantMenu::STATUS_PUBLISHED,
            'menu_mode' => RestaurantMenu::MODE_STRUCTURED,
            'menu_file_path' => null,
            'menu_url' => null,
            'description' => null,
            'notes' => null,
            'display_order' => 0,
        ]);

        $bBranchMenu = RestaurantMenu::create([
            'restaurant_id' => $b->id,
            'branch_id' => $bBranch->id,
            'title' => 'B Branch Menu',
            'slug' => 'b-branch-menu',
            'status' => RestaurantMenu::STATUS_PUBLISHED,
            'menu_mode' => RestaurantMenu::MODE_STRUCTURED,
            'menu_file_path' => null,
            'menu_url' => null,
            'description' => null,
            'notes' => null,
            'display_order' => 0,
        ]);

        return compact('a', 'b', 'aBranch', 'bBranch', 'aWide', 'aBranchMenu', 'bBranchMenu');
    }

    private function assertDeniedOrNotFound(int $status): void
    {
        $this->assertTrue(in_array($status, [403, 404], true), "Expected 403/404, got {$status}");
    }

    public function test_platform_can_list_menus_and_open_create(): void
    {
        $data = $this->seedRestaurantsAndMenus();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        $index = $this->get(route('filament.platform.resources.restaurant-menus.index'));
        $index->assertOk();
        $index->assertSee($data['aWide']->title);
        $index->assertSee('Add menu');

        $this->get(route('filament.platform.resources.restaurant-menus.create'))->assertOk();
    }

    public function test_platform_create_structured_menu_shows_save_first_helper(): void
    {
        $this->seed(RolesAndTestUsersSeeder::class);

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        $this->get(route('filament.platform.resources.restaurant-menus.create'))
            ->assertOk()
            ->assertSee('Save the menu first')
            ->assertDontSee('Menu builder');
    }

    public function test_platform_edit_structured_menu_shows_menu_builder(): void
    {
        $data = $this->seedRestaurantsAndMenus();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformEditRestaurantMenu::class, ['record' => $data['aWide']->getKey()])
            ->assertSuccessful()
            ->assertSee('Menu builder');
    }

    public function test_platform_edit_pdf_menu_hides_menu_content(): void
    {
        $data = $this->seedRestaurantsAndMenus();

        $pdfMenu = RestaurantMenu::create([
            'restaurant_id' => $data['a']->id,
            'branch_id' => null,
            'title' => 'PDF QA Menu',
            'slug' => 'pdf-qa-menu',
            'status' => RestaurantMenu::STATUS_DRAFT,
            'menu_mode' => RestaurantMenu::MODE_PDF_UPLOAD,
            'menu_file_path' => 'menus/fixture-menu.pdf',
            'menu_url' => null,
            'description' => null,
            'notes' => null,
            'display_order' => 0,
        ]);

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformEditRestaurantMenu::class, ['record' => $pdfMenu->getKey()])
            ->assertSuccessful()
            ->assertDontSee('Menu builder')
            ->assertSee('PDF menu');
    }

    public function test_platform_edit_external_menu_hides_menu_content(): void
    {
        $data = $this->seedRestaurantsAndMenus();

        $linkMenu = RestaurantMenu::create([
            'restaurant_id' => $data['a']->id,
            'branch_id' => null,
            'title' => 'Link QA Menu',
            'slug' => 'link-qa-menu',
            'status' => RestaurantMenu::STATUS_DRAFT,
            'menu_mode' => RestaurantMenu::MODE_EXTERNAL_LINK,
            'menu_file_path' => null,
            'menu_url' => 'https://example.com/menu.pdf',
            'description' => null,
            'notes' => null,
            'display_order' => 0,
        ]);

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformEditRestaurantMenu::class, ['record' => $linkMenu->getKey()])
            ->assertSuccessful()
            ->assertDontSee('Menu builder')
            ->assertSee('External menu');
    }

    public function test_platform_view_menu_sections_match_menu_mode(): void
    {
        $data = $this->seedRestaurantsAndMenus();

        $pdfMenu = RestaurantMenu::create([
            'restaurant_id' => $data['a']->id,
            'branch_id' => null,
            'title' => 'PDF View QA',
            'slug' => 'pdf-view-qa',
            'status' => RestaurantMenu::STATUS_DRAFT,
            'menu_mode' => RestaurantMenu::MODE_PDF_UPLOAD,
            'menu_file_path' => 'menus/fixture-view.pdf',
            'menu_url' => null,
            'description' => null,
            'notes' => null,
            'display_order' => 0,
        ]);

        $linkMenu = RestaurantMenu::create([
            'restaurant_id' => $data['a']->id,
            'branch_id' => null,
            'title' => 'Link View QA',
            'slug' => 'link-view-qa',
            'status' => RestaurantMenu::STATUS_DRAFT,
            'menu_mode' => RestaurantMenu::MODE_EXTERNAL_LINK,
            'menu_file_path' => null,
            'menu_url' => 'https://example.com/menu',
            'description' => null,
            'notes' => null,
            'display_order' => 0,
        ]);

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformViewRestaurantMenu::class, ['record' => $data['aWide']->getKey()])
            ->assertSuccessful()
            ->assertSee('Menu preview')
            ->assertDontSee('PDF menu');

        Livewire::test(PlatformViewRestaurantMenu::class, ['record' => $pdfMenu->getKey()])
            ->assertSuccessful()
            ->assertDontSee('Menu preview')
            ->assertSee('PDF menu');

        Livewire::test(PlatformViewRestaurantMenu::class, ['record' => $linkMenu->getKey()])
            ->assertSuccessful()
            ->assertDontSee('Menu preview')
            ->assertSee('External menu');
    }

    public function test_public_disk_url_can_be_root_relative(): void
    {
        Config::set('filesystems.disks.public.url', '/storage');

        $this->assertSame('/storage/menus/sample.pdf', Storage::disk('public')->url('menus/sample.pdf'));
    }

    public function test_platform_structured_menu_content_lists_categories_and_items_table(): void
    {
        $data = $this->seedRestaurantsAndMenus();

        $category = RestaurantMenuCategory::create([
            'restaurant_menu_id' => $data['aWide']->id,
            'name' => 'Starters',
            'description' => null,
            'display_order' => 0,
            'is_active' => true,
        ]);

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(RestaurantMenuStructuredContent::class, ['record' => $data['aWide']])
            ->assertSuccessful()
            ->assertSee('Menu builder')
            ->assertSee('Add category')
            ->assertSee('Add item')
            ->assertSee('Categories')
            ->assertSee('Items')
            ->assertSee($category->name);

        Livewire::test(RestaurantMenuStructuredItemsTable::class, ['menuId' => $data['aWide']->id])
            ->assertSuccessful()
            ->assertSee('No menu items yet.')
            ->assertSee('Use Add item to create the first item.');
    }

    public function test_platform_can_create_structured_menu_via_livewire(): void
    {
        $data = $this->seedRestaurantsAndMenus();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformCreateRestaurantMenu::class)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', $data['aBranch']->id)
            ->set('data.title', 'Lunch Menu')
            ->set('data.slug', 'lunch-menu-test')
            ->set('data.status', RestaurantMenu::STATUS_DRAFT)
            ->set('data.menu_mode', RestaurantMenu::MODE_STRUCTURED)
            ->set('data.display_order', 0)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('restaurant_menus', [
            'slug' => 'lunch-menu-test',
            'restaurant_id' => $data['a']->id,
            'branch_id' => $data['aBranch']->id,
            'menu_mode' => RestaurantMenu::MODE_STRUCTURED,
        ]);
    }

    public function test_restaurant_owner_sees_restaurant_wide_and_branch_menus(): void
    {
        $data = $this->seedRestaurantsAndMenus();

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

        $index = $this->get('/restaurant/restaurant-menus');
        $index->assertOk();
        $index->assertSee($data['aWide']->title);
        $index->assertSee($data['aBranchMenu']->title);
        $index->assertSee('Add menu');
        $index->assertDontSee($data['bBranchMenu']->title);
    }

    public function test_branch_manager_sees_only_branch_scoped_menus(): void
    {
        $data = $this->seedRestaurantsAndMenus();

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

        $index = $this->get('/restaurant/restaurant-menus');
        $index->assertOk();
        $index->assertSee($data['aBranchMenu']->title);
        $index->assertDontSee($data['aWide']->title);
        $index->assertDontSee($data['bBranchMenu']->title);

        $this->get("/restaurant/restaurant-menus/{$data['aWide']->id}")
            ->tap(fn ($resp) => $this->assertDeniedOrNotFound($resp->getStatusCode()));
    }

    public function test_owner_cannot_open_out_of_scope_menu(): void
    {
        $data = $this->seedRestaurantsAndMenus();

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

        $this->get("/restaurant/restaurant-menus/{$data['bBranchMenu']->id}")
            ->tap(fn ($resp) => $this->assertDeniedOrNotFound($resp->getStatusCode()));
    }

    public function test_platform_create_rejects_branch_from_another_restaurant(): void
    {
        $data = $this->seedRestaurantsAndMenus();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformCreateRestaurantMenu::class)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', $data['bBranch']->id)
            ->set('data.title', 'Bad Branch Menu')
            ->set('data.slug', 'bad-branch-menu')
            ->set('data.status', RestaurantMenu::STATUS_DRAFT)
            ->set('data.menu_mode', RestaurantMenu::MODE_STRUCTURED)
            ->set('data.display_order', 0)
            ->call('create')
            ->assertHasErrors(['data.branch_id']);
    }

    public function test_pdf_upload_mode_requires_file_path(): void
    {
        $data = $this->seedRestaurantsAndMenus();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformCreateRestaurantMenu::class)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', null)
            ->set('data.title', 'PDF Menu')
            ->set('data.slug', 'pdf-menu-test')
            ->set('data.status', RestaurantMenu::STATUS_DRAFT)
            ->set('data.menu_mode', RestaurantMenu::MODE_PDF_UPLOAD)
            ->set('data.menu_file_path', null)
            ->set('data.display_order', 0)
            ->call('create')
            ->assertHasErrors();
    }

    public function test_model_rejects_pdf_upload_without_storage_path(): void
    {
        $data = $this->seedRestaurantsAndMenus();

        $this->expectException(ValidationException::class);

        RestaurantMenu::create([
            'restaurant_id' => $data['a']->id,
            'branch_id' => null,
            'title' => 'PDF Menu Direct',
            'slug' => 'pdf-menu-direct',
            'status' => RestaurantMenu::STATUS_DRAFT,
            'menu_mode' => RestaurantMenu::MODE_PDF_UPLOAD,
            'menu_file_path' => null,
            'menu_url' => null,
            'description' => null,
            'notes' => null,
            'display_order' => 0,
        ]);
    }

    public function test_external_link_requires_valid_url(): void
    {
        $data = $this->seedRestaurantsAndMenus();

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformCreateRestaurantMenu::class)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', null)
            ->set('data.title', 'Link Menu Bad')
            ->set('data.slug', 'link-menu-bad')
            ->set('data.status', RestaurantMenu::STATUS_DRAFT)
            ->set('data.menu_mode', RestaurantMenu::MODE_EXTERNAL_LINK)
            ->set('data.menu_url', 'not-a-valid-url')
            ->set('data.display_order', 0)
            ->call('create')
            ->assertHasErrors(['data.menu_url']);

        Livewire::test(PlatformCreateRestaurantMenu::class)
            ->set('data.restaurant_id', $data['a']->id)
            ->set('data.branch_id', null)
            ->set('data.title', 'Link Menu Good')
            ->set('data.slug', 'link-menu-good')
            ->set('data.status', RestaurantMenu::STATUS_DRAFT)
            ->set('data.menu_mode', RestaurantMenu::MODE_EXTERNAL_LINK)
            ->set('data.menu_url', 'https://example.com/menu')
            ->set('data.display_order', 0)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('restaurant_menus', [
            'slug' => 'link-menu-good',
            'menu_mode' => RestaurantMenu::MODE_EXTERNAL_LINK,
            'menu_url' => 'https://example.com/menu',
        ]);
    }

    public function test_can_create_category_and_item_under_structured_menu(): void
    {
        $data = $this->seedRestaurantsAndMenus();

        $category = RestaurantMenuCategory::create([
            'restaurant_menu_id' => $data['aWide']->id,
            'name' => 'Drinks',
            'description' => null,
            'display_order' => 0,
            'is_active' => true,
        ]);

        $item = RestaurantMenuItem::create([
            'restaurant_menu_category_id' => $category->id,
            'name' => 'Coffee',
            'description' => null,
            'price' => 2.50,
            'currency' => 'IQD',
            'is_available' => true,
            'is_featured' => false,
            'display_order' => 0,
            'notes' => null,
        ]);

        $this->assertDatabaseHas('restaurant_menu_categories', [
            'restaurant_menu_id' => $data['aWide']->id,
            'name' => 'Drinks',
        ]);

        $this->assertDatabaseHas('restaurant_menu_items', [
            'restaurant_menu_category_id' => $category->id,
            'name' => 'Coffee',
            'currency' => 'IQD',
        ]);

        $this->assertSame($item->currency, 'IQD');
    }

    public function test_restaurant_menu_item_persists_image_path(): void
    {
        $data = $this->seedRestaurantsAndMenus();

        $category = RestaurantMenuCategory::create([
            'restaurant_menu_id' => $data['aWide']->id,
            'name' => 'With photos',
            'description' => null,
            'display_order' => 0,
            'is_active' => true,
        ]);

        $path = 'menus/items/test-photo.png';

        $item = RestaurantMenuItem::create([
            'restaurant_menu_category_id' => $category->id,
            'name' => 'Photo dish',
            'description' => null,
            'image_path' => $path,
            'price' => 10,
            'currency' => 'IQD',
            'is_available' => true,
            'is_featured' => false,
            'display_order' => 0,
            'notes' => null,
        ]);

        $this->assertDatabaseHas('restaurant_menu_items', [
            'id' => $item->id,
            'image_path' => $path,
        ]);

        $item->update(['image_path' => null]);

        $this->assertDatabaseHas('restaurant_menu_items', [
            'id' => $item->id,
            'image_path' => null,
        ]);
    }

    public function test_normalize_menu_item_image_path_strips_urls_and_storage_prefix(): void
    {
        Config::set('filesystems.disks.public.url', 'http://fixture.test/storage');
        Config::set('app.url', 'http://fixture.test');

        $this->assertSame(
            'menus/items/a.png',
            RestaurantMenuItem::normalizeStoredImagePath('http://fixture.test/storage/menus/items/a.png'),
        );

        $this->assertSame(
            'menus/items/b.png',
            RestaurantMenuItem::normalizeStoredImagePath('/storage/menus/items/b.png'),
        );

        $this->assertSame(
            'menus/items/c.png',
            RestaurantMenuItem::normalizeStoredImagePath(['menus/items/c.png']),
        );

        $this->assertSame(
            'menus/items/d.png',
            RestaurantMenuItem::normalizeStoredImagePath('["menus/items/d.png"]'),
        );
    }

    public function test_menu_item_save_normalizes_full_url_image_path_to_relative(): void
    {
        $data = $this->seedRestaurantsAndMenus();

        Config::set('filesystems.disks.public.url', 'http://fixture.test/storage');
        Config::set('app.url', 'http://fixture.test');

        $category = RestaurantMenuCategory::create([
            'restaurant_menu_id' => $data['aWide']->id,
            'name' => 'Photos',
            'description' => null,
            'display_order' => 0,
            'is_active' => true,
        ]);

        $item = RestaurantMenuItem::create([
            'restaurant_menu_category_id' => $category->id,
            'name' => 'Dish',
            'description' => null,
            'image_path' => 'http://fixture.test/storage/menus/items/saved.png',
            'price' => 1,
            'currency' => 'IQD',
            'is_available' => true,
            'is_featured' => false,
            'display_order' => 0,
            'notes' => null,
        ]);

        $this->assertSame('menus/items/saved.png', $item->fresh()->image_path);
    }
}
