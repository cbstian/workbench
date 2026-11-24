<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\BoardEntry;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;

/**
 * The board is shared by everyone who can see the project (E11, RN-21).
 *
 * Admins and members of the team, and the super admin, create, edit, mark
 * and delete any note on that project (board.update). Client users see nothing.
 * Pausing, priority, due date and the shared row order use ProjectPolicy::update.
 * Hiding a row is a personal preference and only requires being able to view it.
 */
class BoardEntryPolicy
{
    public function viewAny(User $user, Workspace $workspace): bool
    {
        return $user->ownsWorkspace($workspace) || $user->roleIn($workspace)?->isInternal() === true;
    }

    public function view(User $user, BoardEntry $entry): bool
    {
        return $user->can('view', $entry->project);
    }

    public function create(User $user, Project $project): bool
    {
        return $this->canManage($user, $project);
    }

    public function update(User $user, BoardEntry $entry): bool
    {
        return $this->canManage($user, $entry->project);
    }

    public function delete(User $user, BoardEntry $entry): bool
    {
        return $this->canManage($user, $entry->project);
    }

    private function canManage(User $user, Project $project): bool
    {
        if ($user->ownsWorkspace($project->workspace)) {
            return true;
        }

        return $user->isOnTeamOf($project)
            && $user->hasWorkspacePermission($project->workspace_id, Permission::BoardUpdate);
    }
}
