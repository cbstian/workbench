<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;

/**
 * Only the super admin creates and manages workspaces (RN-25); everybody
 * else sees the ones they were invited to (RN-03).
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
     * See and restore everything in the workspace trash (RN-19).
     */
    public function viewTrash(User $user, Workspace $workspace): bool
    {
        return $user->ownsWorkspace($workspace);
    }
}
