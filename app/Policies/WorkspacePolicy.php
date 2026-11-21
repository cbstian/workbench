<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use App\Models\Workspace;

/**
 * Only the super admin creates and manages workspaces (RN-25); everybody
 * else sees the ones they were invited to (RN-03). Admins create members for
 * their projects (RN-21) and see the trash of their projects.
 */
class WorkspacePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, Workspace $workspace): bool
    {
        return Workspace::query()->visibleTo($user)->whereKey($workspace->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $user->ownsWorkspace($workspace);
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $user->ownsWorkspace($workspace);
    }

    public function restore(User $user, Workspace $workspace): bool
    {
        return $user->ownsWorkspace($workspace);
    }

    /**
     * Create users, change their roles and add existing users (RN-14, RN-26).
     */
    public function manageMembers(User $user, Workspace $workspace): bool
    {
        return $user->ownsWorkspace($workspace);
    }

    /**
     * Create new members with access to the admin's projects (RN-21, A-14).
     */
    public function createMembers(User $user, Workspace $workspace): bool
    {
        return $user->ownsWorkspace($workspace)
            || $user->hasWorkspacePermission($workspace, Permission::MembersCreate);
    }

    /**
     * Open the users page: everyone for the super admin, the teams of their
     * projects for an admin.
     */
    public function viewMembers(User $user, Workspace $workspace): bool
    {
        return $this->createMembers($user, $workspace);
    }

    /**
     * See and restore the workspace trash (RN-19): all of it for the super
     * admin, what belongs to their projects for an admin.
     */
    public function viewTrash(User $user, Workspace $workspace): bool
    {
        return $user->ownsWorkspace($workspace)
            || $user->hasWorkspacePermission($workspace, Permission::ProjectTrash);
    }
}
