<?php

namespace App\Actions\Projects;

use App\Enums\TaskActivityEvent;
use App\Models\ProjectPriority;
use App\Models\ProjectStatus;
use App\Models\Task;
use App\Models\TaskActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Sends a status or a priority to the trash. Its tasks move to the project's
 * initial value first, so a task never stays without one (RN-06, A-13); the
 * initial value itself cannot be deleted (RN-27, A-23).
 */
class DeleteWorkflowValue
{
    public function handle(ProjectStatus|ProjectPriority $value): void
    {
        if ($value->is_initial) {
            throw ValidationException::withMessages([
                'value' => 'Elige otro valor inicial antes de eliminar este.',
            ]);
        }

        $column = $value instanceof ProjectStatus ? 'status_id' : 'priority_id';
        $event = $value instanceof ProjectStatus ? TaskActivityEvent::StatusChanged : TaskActivityEvent::PriorityChanged;
        $field = $value instanceof ProjectStatus ? 'status' : 'priority';

        $initial = $value::query()
            ->where('project_id', $value->project_id)
            ->where('is_initial', true)
            ->firstOrFail();

        DB::transaction(function () use ($value, $initial, $column, $event, $field): void {
            $tasks = Task::withTrashed()->where($column, $value->id)->get(['id', 'workspace_id']);

            foreach ($tasks as $task) {
                Task::withTrashed()->whereKey($task->id)->update([$column => $initial->id]);

                TaskActivity::query()->create([
                    'task_id' => $task->id,
                    'user_id' => Auth::id(),
                    'event' => $event,
                    'changes' => [$field => [
                        'old' => ['id' => $value->id, 'name' => $value->name],
                        'new' => ['id' => $initial->id, 'name' => $initial->name],
                    ]],
                    'metadata' => ['reason' => "{$field}_deleted"],
                ]);
            }

            $value->delete();
        });
    }
}
