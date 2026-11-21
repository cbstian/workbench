<?php

namespace App\Actions\Tasks;

use App\Enums\TaskActivityEvent;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Sends a task to the trash (RN-19) and records it in its history and, for a
 * subtask, in the parent's. Subtasks of a trashed task are not marked: they
 * stay hidden through their parent and come back with it.
 */
class DeleteTask
{
    public function handle(Task $task, User $actor): void
    {
        DB::transaction(function () use ($task, $actor): void {
            TaskActivity::record($task, $actor, TaskActivityEvent::Deleted);

            if ($task->parent !== null) {
                TaskActivity::record($task->parent, $actor, TaskActivityEvent::SubtaskRemoved, metadata: [
                    'subtask_id' => $task->id,
                    'subtask_name' => $task->name,
                ]);
            }

            $task->delete();
        });
    }
}
