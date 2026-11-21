<?php

namespace App\Actions\Tasks;

use App\Enums\TaskActivityEvent;
use App\Models\ProjectPriority;
use App\Models\ProjectStatus;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskLabel;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Saves the changes of a task and records each one in its history (RN-08):
 * data fields together as `updated`, then status, priority, assignees and
 * labels as their own events. Relations are stored with id and name so the
 * history stays readable after they are deleted.
 */
class UpdateTask
{
    /**
     * Data fields grouped in the `updated` event.
     *
     * @var list<string>
     */
    private const array DATA_FIELDS = ['name', 'description', 'starts_on', 'ends_on'];

    /**
     * @param  array{name: string, description: string|null, status_id: int, priority_id: int, starts_on: string|null, ends_on: string|null}  $attributes
     * @param  list<int>  $assigneeIds
     * @param  list<int>  $labelIds
     */
    public function handle(Task $task, User $actor, array $attributes, array $assigneeIds, array $labelIds): void
    {
        DB::transaction(function () use ($task, $actor, $attributes, $assigneeIds, $labelIds): void {
            $oldStatus = $task->status;
            $oldPriority = $task->priority;

            $task->fill($attributes);

            $dataChanges = [];

            foreach (self::DATA_FIELDS as $field) {
                if ($task->isDirty($field)) {
                    $dataChanges[$field] = [
                        'old' => $this->plain($task->getOriginal($field)),
                        'new' => $this->plain($task->getAttribute($field)),
                    ];
                }
            }

            $statusChanged = $task->isDirty('status_id');
            $priorityChanged = $task->isDirty('priority_id');

            $task->save();

            if ($dataChanges !== []) {
                TaskActivity::record($task, $actor, TaskActivityEvent::Updated, $dataChanges);
            }

            if ($statusChanged) {
                TaskActivity::record($task, $actor, TaskActivityEvent::StatusChanged, [
                    'status' => ['old' => $this->ref($oldStatus), 'new' => $this->ref(ProjectStatus::query()->find($task->status_id))],
                ]);
            }

            if ($priorityChanged) {
                TaskActivity::record($task, $actor, TaskActivityEvent::PriorityChanged, [
                    'priority' => ['old' => $this->ref($oldPriority), 'new' => $this->ref(ProjectPriority::query()->find($task->priority_id))],
                ]);
            }

            $this->syncAssignees($task, $actor, $assigneeIds);
            $this->syncLabels($task, $actor, $labelIds);
        });
    }

    /**
     * @param  list<int>  $assigneeIds
     */
    private function syncAssignees(Task $task, User $actor, array $assigneeIds): void
    {
        $current = $task->assignees()->pluck('users.id')->map(fn ($id): int => (int) $id)->all();
        $added = array_values(array_diff($assigneeIds, $current));
        $removed = array_values(array_diff($current, $assigneeIds));

        if ($removed !== []) {
            $task->assignees()->detach($removed);
        }

        if ($added !== []) {
            $task->assignees()->attach(array_fill_keys($added, ['assigned_by_id' => $actor->id]));
        }

        $names = User::withTrashed()->whereKey([...$added, ...$removed])->pluck('name', 'id');

        foreach ($added as $userId) {
            TaskActivity::record($task, $actor, TaskActivityEvent::AssigneeAdded, metadata: ['user_id' => $userId, 'user_name' => $names[$userId] ?? null]);
        }

        foreach ($removed as $userId) {
            TaskActivity::record($task, $actor, TaskActivityEvent::AssigneeRemoved, metadata: ['user_id' => $userId, 'user_name' => $names[$userId] ?? null]);
        }
    }

    /**
     * @param  list<int>  $labelIds
     */
    private function syncLabels(Task $task, User $actor, array $labelIds): void
    {
        $current = $task->labels()->pluck('task_labels.id')->map(fn ($id): int => (int) $id)->all();
        $added = array_values(array_diff($labelIds, $current));
        $removed = array_values(array_diff($current, $labelIds));

        if ($removed !== []) {
            $task->labels()->detach($removed);
        }

        if ($added !== []) {
            $task->labels()->attach($added);
        }

        $names = TaskLabel::withTrashed()->whereKey([...$added, ...$removed])->pluck('name', 'id');

        foreach ($added as $labelId) {
            TaskActivity::record($task, $actor, TaskActivityEvent::LabelAdded, metadata: ['label_id' => $labelId, 'label_name' => $names[$labelId] ?? null]);
        }

        foreach ($removed as $labelId) {
            TaskActivity::record($task, $actor, TaskActivityEvent::LabelRemoved, metadata: ['label_id' => $labelId, 'label_name' => $names[$labelId] ?? null]);
        }
    }

    /**
     * @return array{id: int, name: string}|null
     */
    private function ref(ProjectStatus|ProjectPriority|null $value): ?array
    {
        return $value === null ? null : ['id' => $value->id, 'name' => $value->name];
    }

    private function plain(mixed $value): mixed
    {
        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value;
    }
}
