<?php

namespace App\Actions\Authorization;

use App\Enums\Permission;
use App\Enums\WorkspaceRole;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the permission catalog and the global workspace roles (team_id
 * null, usable in every workspace) and grants each role its permissions.
 *
 * Idempotent: run from a migration and from the seeder; rerun it after
 * changing App\Enums\Permission or WorkspaceRole::permissions().
 */
class SyncRolePermissions
{
    public function handle(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        foreach (Permission::cases() as $permission) {
            SpatiePermission::findOrCreate($permission->value, 'web');
        }

        SpatiePermission::query()
            ->whereNotIn('name', array_map(fn (Permission $permission): string => $permission->value, Permission::cases()))
            ->delete();

        foreach (WorkspaceRole::cases() as $workspaceRole) {
            $role = Role::query()
                ->where('name', $workspaceRole->value)
                ->where('guard_name', 'web')
                ->whereNull($registrar->teamsKey)
                ->first()
                ?? Role::query()->create([
                    'name' => $workspaceRole->value,
                    'guard_name' => 'web',
                    $registrar->teamsKey => null,
                ]);

            $role->syncPermissions(array_map(
                fn (Permission $permission): string => $permission->value,
                $workspaceRole->permissions(),
            ));
        }

        $registrar->forgetCachedPermissions();
    }
}
