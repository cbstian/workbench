<?php

namespace App\Livewire\Roles;

use App\Enums\Permission;
use App\Enums\WorkspaceRole;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

/**
 * Roles and permissions matrix, read from spatie/laravel-permission
 * (docs/permisos.md): what admins, members and client users may do in the
 * projects they are assigned to, and what only the super admin may do.
 */
#[Title('Roles y permisos')]
class Index extends Component
{
    public function mount(): void
    {
        Gate::authorize('manage-account');
    }

    /**
     * Permission names granted to each workspace role, as stored by spatie.
     *
     * @return array<string, list<string>>
     */
    #[Computed]
    public function grants(): array
    {
        return Role::query()
            ->whereNull(config('permission.column_names.team_foreign_key'))
            ->with('permissions:id,name')
            ->get()
            ->mapWithKeys(fn (Role $role): array => [$role->name => $role->permissions->pluck('name')->all()])
            ->all();
    }

    /**
     * @return list<WorkspaceRole>
     */
    #[Computed]
    public function roles(): array
    {
        return WorkspaceRole::cases();
    }

    /**
     * @return list<Permission>
     */
    #[Computed]
    public function permissions(): array
    {
        return Permission::cases();
    }

    public function render(): View
    {
        return view('livewire.roles.index');
    }
}
