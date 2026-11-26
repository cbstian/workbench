<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\WorkspaceRole;
use App\Models\Page;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;

/**
 * Wiki access (F-09, RN-24): only the super admin reaches the workspace wiki;
 * admins and members reach the whole wiki of their projects (wiki.project).
 * Whoever reaches a wiki creates, edits, moves and trashes its pages, and
 * comments on them.
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

    /**
     * Comment and reply: whoever reaches the page.
     */
    public function comment(User $user, Page $page): bool
    {
        return $this->view($user, $page);
    }

    /**
     * Delete anybody's comment: the super admin and the project's admins.
     * Authors may always remove their own.
     */
    public function moderate(User $user, Page $page): bool
    {
        if (! $this->view($user, $page)) {
            return false;
        }

        return $user->ownsWorkspace($page->workspace)
            || ($page->project_id !== null && $user->roleIn($page->workspace) === WorkspaceRole::Admin);
    }
}
