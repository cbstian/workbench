<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

/**
 * Tasks of a project (F-06, RN-21, RN-23): the super admin and the project's
 * admins act on every task; a member sees every task of their projects,
 * creates tasks and acts only on the ones assigned to them (or whose parent
 * task is assigned to them).
 */
class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        if (! $this->isReachable($task)) {
            return false;
        }

        if ($user->ownsWorkspace($task->project->workspace)) {
            return true;
        }

        return $user->isOnTeamOf($task->project)
            && $user->hasWorkspacePermission($task->workspace_id, Permission::TasksView);
    }

    public function create(User $user, Project $project): bool
    {
        if ($user->ownsWorkspace($project->workspace)) {
            return true;
        }

        return $user->isOnTeamOf($project)
            && $user->hasWorkspacePermission($project->workspace_id, Permission::TasksCreate);
    }

    public function update(User $user, Task $task): bool
    {
        if (! $this->isReachable($task)) {
            return false;
        }

        if ($user->ownsWorkspace($task->project->workspace)) {
            return true;
        }

        if (! $user->isOnTeamOf($task->project)) {
            return false;
        }

        if ($user->hasWorkspacePermission($task->workspace_id, Permission::TasksUpdateAny)) {
            return true;
        }

        return $user->hasWorkspacePermission($task->workspace_id, Permission::TasksUpdateAssigned)
            && $this->isAssignedTo($user, $task);
    }

    /**
     * Send to the trash: same rule as editing.
     */
    public function delete(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }

    /**
     * Comment and reply: whoever sees the task, assigned or not (RN-10,
     * revisada 2026-11-26), so a mentioned teammate can answer.
     */
    public function comment(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }

    /**
     * Attach files: whoever may act on the task (F-07).
     */
    public function collaborate(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }

    /**
     * Delete anybody's comment or attachment: the super admin and the
     * project's admins. Authors may always remove their own.
     */
    public function moderate(User $user, Task $task): bool
    {
        if (! $this->isReachable($task)) {
            return false;
        }

        return $user->ownsWorkspace($task->project->workspace)
            || ($user->isOnTeamOf($task->project) && $user->hasWorkspacePermission($task->workspace_id, Permission::TasksUpdateAny));
    }

    /**
     * Add subtasks: editing rights on a root task (RN-17).
     */
    public function addSubtask(User $user, Task $task): bool
    {
        return $task->parent_id === null && $this->update($user, $task);
    }

    /**
     * A task of a trashed project, or a subtask of a trashed task, stays
     * hidden until its root is restored (docs/arquitectura/modelo-de-datos.md, 7).
     */
    private function isReachable(Task $task): bool
    {
        return $task->project !== null && ($task->parent_id === null || $task->parent !== null);
    }

    private function isAssignedTo(User $user, Task $task): bool
    {
        return $task->assignees()->whereKey($user->id)->exists()
            || ($task->parent_id !== null && $task->parent?->assignees()->whereKey($user->id)->exists());
    }
}
