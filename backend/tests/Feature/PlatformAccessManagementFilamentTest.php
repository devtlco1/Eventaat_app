<?php

namespace Tests\Feature;

use App\Filament\Platform\Resources\Permissions\PermissionResource;
use App\Filament\Platform\Resources\Roles\Pages\CreateRole;
use App\Filament\Platform\Resources\Roles\Pages\EditRole;
use App\Filament\Platform\Resources\Roles\Pages\ListRoles;
use App\Filament\Platform\Resources\Roles\RoleResource;
use App\Filament\Platform\Resources\Users\Pages\CreateUser;
use App\Filament\Platform\Resources\Users\Pages\EditUser;
use App\Models\User;
use Database\Seeders\PermissionsCatalogSeeder;
use Database\Seeders\RolePermissionDefaultsSeeder;
use Database\Seeders\RolesAndTestUsersSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlatformAccessManagementFilamentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
        $this->seed(RolePermissionDefaultsSeeder::class);
    }

    private function user(string $email): User
    {
        /** @var User $user */
        $user = User::where('email', $email)->firstOrFail();

        return $user;
    }

    public function test_permissions_catalog_seeder_is_idempotent(): void
    {
        $this->seed(PermissionsCatalogSeeder::class);
        $first = Permission::query()->where('guard_name', 'web')->count();
        $this->seed(PermissionsCatalogSeeder::class);
        $this->assertSame($first, Permission::query()->where('guard_name', 'web')->count());
        $this->assertSame(count(PermissionsCatalogSeeder::names()), Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', PermissionsCatalogSeeder::names())
            ->count());
    }

    public function test_super_admin_users_index_shows_add_user_button(): void
    {
        Filament::setCurrentPanel('platform');
        $this->actingAs($this->user('super_admin@eventaat.test'));

        $this->get('/platform/users')->assertOk()->assertSee('Add user');
    }

    public function test_operations_admin_users_index_hides_add_user_button(): void
    {
        Filament::setCurrentPanel('platform');
        $this->actingAs($this->user('operations_admin@eventaat.test'));

        $this->get('/platform/users')->assertOk()->assertDontSee('Add user');
    }

    public function test_super_admin_roles_index_shows_add_role_button(): void
    {
        Filament::setCurrentPanel('platform');
        $this->actingAs($this->user('super_admin@eventaat.test'));

        $this->get('/platform/roles')->assertOk()->assertSee('Add role');
    }

    public function test_super_admin_role_permission_defaults_are_applied(): void
    {
        $catalogCount = count(PermissionsCatalogSeeder::names());

        $superAdmin = Role::findByName('super_admin', 'web');
        $this->assertNotNull($superAdmin);
        $this->assertSame($catalogCount, $superAdmin->permissions()->count());

        $operationsAdmin = Role::findByName('operations_admin', 'web');
        $this->assertNotNull($operationsAdmin);
        $this->assertSame($catalogCount - 3, $operationsAdmin->permissions()->count());
        $this->assertFalse($operationsAdmin->hasPermissionTo('roles.manage'));
        $this->assertTrue($operationsAdmin->hasPermissionTo('users.manage'));
    }

    public function test_super_admin_can_open_users_roles_and_permissions_indexes(): void
    {
        Filament::setCurrentPanel('platform');
        $this->actingAs($this->user('super_admin@eventaat.test'));

        $this->get('/platform/users')->assertOk();
        $this->get('/platform/roles')->assertOk();
        $this->get('/platform/permissions')->assertOk();
    }

    public function test_operations_admin_can_open_users_but_not_roles_or_permissions(): void
    {
        Filament::setCurrentPanel('platform');
        $this->actingAs($this->user('operations_admin@eventaat.test'));

        $this->get('/platform/users')->assertOk();
        $this->get('/platform/roles')->assertForbidden();
        $this->get('/platform/permissions')->assertForbidden();
    }

    public function test_operations_admin_cannot_open_user_create_page(): void
    {
        Filament::setCurrentPanel('platform');
        $this->actingAs($this->user('operations_admin@eventaat.test'));

        $this->get('/platform/users/create')->assertForbidden();
    }

    public function test_super_admin_can_open_user_create_page(): void
    {
        Filament::setCurrentPanel('platform');
        $this->actingAs($this->user('super_admin@eventaat.test'));

        $this->get('/platform/users/create')->assertOk();
    }

    public function test_restaurant_roles_cannot_open_platform_access_pages(): void
    {
        Filament::setCurrentPanel('platform');

        foreach (['restaurant_owner@eventaat.test', 'branch_manager@eventaat.test', 'restaurant_host@eventaat.test'] as $email) {
            $this->actingAs($this->user($email));

            $this->get('/platform/users')->assertForbidden();
            $this->get('/platform/roles')->assertForbidden();
            $this->get('/platform/permissions')->assertForbidden();
        }
    }

    public function test_customer_cannot_open_platform_access_pages(): void
    {
        Filament::setCurrentPanel('platform');
        $this->actingAs($this->user('customer@eventaat.test'));

        $this->get('/platform/users')->assertForbidden();
        $this->get('/platform/roles')->assertForbidden();
        $this->get('/platform/permissions')->assertForbidden();
    }

    public function test_core_roles_are_not_deletable_via_authorization(): void
    {
        Filament::setCurrentPanel('platform');
        $this->actingAs($this->user('super_admin@eventaat.test'));

        foreach (['super_admin', 'operations_admin', 'customer'] as $roleName) {
            $role = Role::findByName($roleName, 'web');
            $this->assertNotNull($role);
            $this->assertFalse(RoleResource::canDelete($role));
        }
    }

    public function test_core_role_delete_table_action_is_hidden_for_super_admin(): void
    {
        Filament::setCurrentPanel('platform');
        $this->actingAs($this->user('super_admin@eventaat.test'));

        Livewire::test(ListRoles::class)
            ->assertTableActionHidden('delete', Role::findByName('customer', 'web'));
    }

    public function test_non_core_role_delete_table_action_is_visible_for_super_admin(): void
    {
        Filament::setCurrentPanel('platform');
        $this->actingAs($this->user('super_admin@eventaat.test'));

        $role = Role::create([
            'name' => 'qa_visible_delete_role',
            'guard_name' => 'web',
        ]);

        Livewire::test(ListRoles::class)
            ->assertTableActionVisible('delete', $role);
    }

    public function test_super_admin_can_delete_non_core_roles(): void
    {
        Filament::setCurrentPanel('platform');
        $this->actingAs($this->user('super_admin@eventaat.test'));

        $role = Role::create([
            'name' => 'temp_filament_role_qa',
            'guard_name' => 'web',
        ]);

        $this->assertTrue(RoleResource::canDelete($role));
    }

    public function test_permissions_resource_is_read_only_at_authorization_level(): void
    {
        Filament::setCurrentPanel('platform');
        $this->actingAs($this->user('super_admin@eventaat.test'));

        $permission = Permission::firstOrCreate(
            ['name' => 'qa.temp.permission', 'guard_name' => 'web'],
        );

        $this->assertFalse(PermissionResource::canCreate());
        $this->assertFalse(PermissionResource::canEdit($permission));
        $this->assertFalse(PermissionResource::canDelete($permission));
    }

    public function test_super_admin_can_assign_roles_via_edit_user_livewire(): void
    {
        $subject = User::create([
            'name' => 'Role Target',
            'email' => 'role-target-test@eventaat.test',
            'password' => Hash::make('password'),
        ]);
        $subject->syncRoles(['customer']);

        $ownerRoleId = Role::findByName('restaurant_owner', 'web')->getKey();

        Filament::setCurrentPanel('platform');
        $this->actingAs($this->user('super_admin@eventaat.test'));

        Livewire::test(EditUser::class, ['record' => $subject->getKey()])
            ->set('data.roles', [$ownerRoleId])
            ->call('save')
            ->assertHasNoErrors();

        $subject->refresh();
        $this->assertTrue($subject->hasRole('restaurant_owner'));
        $this->assertFalse($subject->hasRole('customer'));
    }

    public function test_operations_admin_can_edit_profile_without_changing_roles_livewire(): void
    {
        $subject = User::create([
            'name' => 'Ops Role Preserve',
            'email' => 'ops-role-preserve@eventaat.test',
            'password' => Hash::make('password'),
        ]);
        $subject->syncRoles(['customer']);

        Filament::setCurrentPanel('platform');
        $this->actingAs($this->user('operations_admin@eventaat.test'));

        Livewire::test(EditUser::class, ['record' => $subject->getKey()])
            ->set('data.name', 'Ops Role Preserve Updated')
            ->call('save')
            ->assertHasNoErrors();

        $subject->refresh();
        $this->assertSame('Ops Role Preserve Updated', $subject->name);
        $this->assertTrue($subject->hasRole('customer'));
        $this->assertFalse($subject->hasRole('super_admin'));
    }

    public function test_super_admin_can_create_user_with_customer_role_livewire(): void
    {
        Filament::setCurrentPanel('platform');
        $this->actingAs($this->user('super_admin@eventaat.test'));

        $customerRoleId = Role::findByName('customer', 'web')->getKey();

        Livewire::test(CreateUser::class)
            ->set('data.name', 'New Customer User')
            ->set('data.email', 'new-customer-livewire@eventaat.test')
            ->set('data.phone', '+15550009988')
            ->set('data.password', 'password-password')
            ->set('data.roles', [$customerRoleId])
            ->call('create')
            ->assertHasNoErrors();

        $created = User::where('email', 'new-customer-livewire@eventaat.test')->firstOrFail();
        $this->assertTrue($created->hasRole('customer'));
    }

    public function test_super_admin_can_create_custom_role_via_livewire(): void
    {
        Filament::setCurrentPanel('platform');
        $this->actingAs($this->user('super_admin@eventaat.test'));

        Livewire::test(CreateRole::class)
            ->set('data.name', 'qa_filament_role_create')
            ->set('data.guard_name', 'web')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertNotNull(Role::findByName('qa_filament_role_create', 'web'));
    }

    public function test_super_admin_can_attach_permission_to_role_via_livewire(): void
    {
        $role = Role::create([
            'name' => 'qa_perm_attach_role',
            'guard_name' => 'web',
        ]);

        $permissionId = Permission::findByName('bookings.manage', 'web')?->getKey();
        $this->assertNotNull($permissionId);

        Filament::setCurrentPanel('platform');
        $this->actingAs($this->user('super_admin@eventaat.test'));

        Livewire::test(EditRole::class, ['record' => $role->getKey()])
            ->set('data.permissions', [$permissionId])
            ->call('save')
            ->assertHasNoErrors();

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('bookings.manage'));
    }
}
