<?php

namespace App\Actions\Tasks;

use App\Enums\TaskActivityEvent;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Creates a task or a subtask (F-06).
 *
 * Without an explicit status or priority the task takes the project's initial
 * values (RN-22); it goes last in its list and its creation is recorded in its
 * history (RN-08), and in the parent's history for a subtask.
 */
class CreateTask
{
    /**
     * @param  array{name: string, description?: string|null, status_id?: int|null, priority_id?: int|null, starts_on?: string|null, ends_on?: string|null}  $attributes
     * @param  list<int>  $assigneeIds
     * @param  list<int>  $labelIds
     */
    public function handle(Project $project, User $actor, array $attributes, array $assigneeIds = [], array $labelIds = [], ?Task $parent = null): Task
    {
        if ($parent !== null && ($parent->parent_id !== null || $parent->project_id !== $project->id)) {
            throw new InvalidArgumentException('A subtask needs a root task of the same project (RN-17).');
        }

        return DB::transaction(function () use ($project, $actor, $attributes, $assigneeIds, $labelIds, $parent): Task {
            $position = (int) Task::withTrashed()
                ->where('project_id', $project->id)
                ->where('parent_id', $parent?->id)
                ->max('position') + 1024;

            $task = Task::query()->create([
                'project_id' => $project->id,
                'parent_id' => $parent?->id,
                'status_id' => $attributes['status_id'] ?? $project->initialStatus()->value('id'),
                'priority_id' => $attributes['priority_id'] ?? $project->initialPriority()->value('id'),
                'name' => $attributes['name'],
                'description' => $attributes['description'] ?? null,
                'starts_on' => $attributes['starts_on'] ?? null,
                'ends_on' => $attributes['ends_on'] ?? null,
                'position' => $position,
                'created_by_id' => $actor->id,
            ]);

            if ($assigneeIds !== []) {
                $task->assignees()->attach(array_fill_keys($assigneeIds, ['assigned_by_id' => $actor->id]));
            }

            if ($labelIds !== []) {
                $task->labels()->attach($labelIds);
            }

            TaskActivity::record($task, $actor, TaskActivityEvent::Created);

            if ($parent !== null) {
                TaskActivity::record($parent, $actor, TaskActivityEvent::SubtaskAdded, metadata: [
                    'subtask_id' => $task->id,
                    'subtask_name' => $task->name,
                ]);
            }

            return $task;
        });
    }
}
