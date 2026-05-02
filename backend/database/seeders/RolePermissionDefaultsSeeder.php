<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Attaches catalog permissions to platform roles for metadata/readiness only.
 * Restaurant roles and customers intentionally receive no platform permission rows here.
 */
class RolePermissionDefaultsSeeder extends Seeder
{
    /**
     * Permissions withheld from operations_admin (still governed by Filament role checks).
     *
     * @var list<string>
     */
    private const OPERATIONS_ADMIN_EXCLUDED = [
        'roles.view',
        'roles.manage',
        'permissions.view',
    ];

    public function run(): void
    {
        $this->call(PermissionsCatalogSeeder::class);

        $catalogNames = PermissionsCatalogSeeder::names();

        $allCatalog = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $catalogNames)
            ->orderBy('name')
            ->get();

        $superAdmin = Role::findByName('super_admin', 'web');
        if ($superAdmin) {
            $superAdmin->syncPermissions($allCatalog);
        }

        $operationsAdmin = Role::findByName('operations_admin', 'web');
        if ($operationsAdmin) {
            $allowedNames = collect($catalogNames)
                ->reject(fn (string $name): bool => in_array($name, self::OPERATIONS_ADMIN_EXCLUDED, true))
                ->values()
                ->all();

            $operationsPermissions = Permission::query()
                ->where('guard_name', 'web')
                ->whereIn('name', $allowedNames)
                ->orderBy('name')
                ->get();

            $operationsAdmin->syncPermissions($operationsPermissions);
        }
    }
}
