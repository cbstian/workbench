<?php

namespace App\Actions\Tasks;

use App\Enums\StatusCategory;
use App\Models\ProjectStatus;
use App\Models\Task;
use App\Models\User;

/**
 * Moves a task to the first status of its project in a status category, at
 * the end of that column. Used where statuses of several projects meet (the
 * global kanban, the home page), since each project names its own statuses.
 *
 * Authorization is the caller's job.
 */
class MoveTaskToCategory
{
    public function __construct(private MoveTask $moveTask) {}

    /**
     * @return ProjectStatus|null The status the task ended in, or null when
     *                            the project has no status in that category.
     */
    public function handle(Task $task, User $actor, StatusCategory $category): ?ProjectStatus
    {
        $status = ProjectStatus::query()
            ->where('project_id', $task->project_id)
            ->where('category', $category)
            ->orderBy('position')
            ->first();

        if ($status === null) {
            return null;
        }

        $this->moveTask->handle($task, $actor, $status, null, null);

        return $status;
    }
}
