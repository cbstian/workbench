<?php

namespace App\Support;

use App\Enums\Permission;
use App\Enums\WorkspaceRole;
use App\Models\Concerns\Commentable;
use App\Models\Page;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;

/**
 * What a client user sees in their portal (P-04, P-06, P-14, decididas
 * 2026-11-26). It is the single source of the rules; the policies ask it.
 *
 * - Projects of their clients marked visible to the client, with every task
 *   and subtask.
 * - Wiki pages marked visible whose ancestors are all visible too, in those
 *   projects or in the workspace wiki.
 */
final class ClientPortal
{
    /**
     * Whether the user enters the portal of the workspace: a client user with
     * portal access who belongs to at least one client there.
     */
    public function isClientIn(User $user, Workspace|int $workspace): bool
    {
        $workspaceId = $workspace instanceof Workspace ? $workspace->id : $workspace;
        $workspace = $workspace instanceof Workspace ? $workspace : Workspace::query()->find($workspaceId);

        return $workspace !== null
            && $user->roleIn($workspace) === WorkspaceRole::Client
            && $user->hasWorkspacePermission($workspaceId, Permission::PortalView)
            && $user->clients()->where('clients.workspace_id', $workspaceId)->exists();
    }

    public function canSeeProject(User $user, Project $project): bool
    {
        return ! $project->trashed()
            && $project->is_visible_to_client
            && $project->client_id !== null
            && $this->isClientIn($user, $project->workspace_id)
            && $user->clients()->whereKey($project->client_id)->exists();
    }

    /**
     * A task of a visible project, unless it or its parent is in the trash.
     */
    public function canSeeTask(User $user, Task $task): bool
    {
        return $task->project !== null
            && ($task->parent_id === null || $task->parent !== null)
            && $this->canSeeProject($user, $task->project);
    }

    public function canSeePage(User $user, Page $page): bool
    {
        if (! $this->isExposed($page)) {
            return false;
        }

        return $page->project_id === null
            ? $this->isClientIn($user, $page->workspace_id)
            : $this->canSeeProject($user, $page->project);
    }

    /**
     * Whether a task or page is shown to client users at all, so the team
     * gets its conversation with the client.
     */
    public function isExposed(Commentable $commentable): bool
    {
        return match (true) {
            $commentable instanceof Task => $commentable->project !== null
                && $commentable->project->is_visible_to_client
                && $commentable->project->client_id !== null,
            $commentable instanceof Page => $commentable->isInActiveTree()
                && $this->hasVisibleAncestry($commentable)
                && ($commentable->project_id === null || ($commentable->project->is_visible_to_client && $commentable->project->client_id !== null)),
            default => false,
        };
    }

    /**
     * Pages of a wiki (a project's, or the workspace's when null) that client
     * users see, in tree order: parents first, siblings by position.
     *
     * @return Collection<int, Page>
     */
    public function visiblePages(Workspace $workspace, ?Project $project): Collection
    {
        $pages = Page::query()
            ->inWiki($workspace, $project)
            ->select(['id', 'uuid', 'workspace_id', 'project_id', 'parent_id', 'title', 'icon', 'position', 'is_visible_to_client', 'updated_at'])
            ->orderBy('position')
            ->orderBy('title')
            ->get();

        $byParent = $pages->groupBy(fn (Page $page): int => $page->parent_id ?? 0);
        $visible = new Collection;

        $walk = function (int $parentId, int $depth) use (&$walk, $byParent, $visible): void {
            foreach ($byParent->get($parentId, []) as $page) {
                if (! $page->is_visible_to_client) {
                    continue;
                }

                $page->setAttribute('depth', $depth);
                $visible->push($page);
                $walk($page->id, $depth + 1);
            }
        };

        $walk(0, 0);

        return $visible;
    }

    /**
     * Where a client user opens a task or page in the portal.
     */
    public function urlFor(Commentable $commentable): string
    {
        return match (true) {
            $commentable instanceof Task => route('portal.projects.show', ['project' => $commentable->project, 'tarea' => $commentable->uuid]),
            $commentable instanceof Page => route('portal.pages.show', $commentable),
            default => route('portal.index'),
        };
    }

    /**
     * The page and all its ancestors are marked visible to the client.
     */
    private function hasVisibleAncestry(Page $page): bool
    {
        if (! $page->is_visible_to_client) {
            return false;
        }

        $parentId = $page->parent_id;

        while ($parentId !== null) {
            $parent = Page::query()->select(['id', 'parent_id', 'is_visible_to_client'])->find($parentId);

            if ($parent === null || ! $parent->is_visible_to_client) {
                return false;
            }

            $parentId = $parent->parent_id;
        }

        return true;
    }
}
