<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Page;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;

/**
 * Wiki access (F-09, RN-24): only the super admin reaches the workspace wiki;
 * admins and members reach the whole wiki of their projects (wiki.project).
 * Whoever reaches a wiki creates, edits, moves and trashes its pages.
 */
class PagePolicy
{
    /**
     * Open the workspace wiki (pages without a project).
     */
    public function viewWorkspaceWiki(User $user, Workspace $workspace): bool
    {
        return $user->ownsWorkspace($workspace);
    }

    /**
     * Open the wiki of a project.
     */
    public function viewProjectWiki(User $user, Project $project): bool
    {
        return $user->ownsWorkspace($project->workspace)
            || ($user->isOnTeamOf($project) && $user->hasWorkspacePermission($project->workspace_id, Permission::ProjectWiki));
    }

    public function view(User $user, Page $page): bool
    {
        if (! $page->isInActiveTree()) {
            return false;
        }

        return $page->project_id === null
            ? $this->viewWorkspaceWiki($user, $page->workspace)
            : $this->viewProjectWiki($user, $page->project);
    }

    public function update(User $user, Page $page): bool
    {
        return $this->view($user, $page);
    }

    public function delete(User $user, Page $page): bool
    {
        return $this->view($user, $page);
    }
}
