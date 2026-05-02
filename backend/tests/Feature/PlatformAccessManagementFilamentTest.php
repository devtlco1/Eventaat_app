<?php

namespace Tests\Feature;

use App\Filament\Platform\Resources\Permissions\PermissionResource;
use App\Filament\Platform\Resources\Roles\RoleResource;
use App\Filament\Platform\Resources\Users\Pages\CreateUser;
use App\Filament\Platform\Resources\Users\Pages\EditUser;
use App\Models\User;
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
    }

    private function user(string $email): User
    {
        /** @var User $user */
        $user = User::where('email', $email)->firstOrFail();

        return $user;
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
}
