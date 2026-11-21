<?php

namespace App\Actions\Tasks;

use App\Enums\TaskActivityEvent;
use App\Models\ProjectStatus;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Moves a root task in the kanban: to another status (recorded in its history,
 * RN-08) and between two neighbors of its column.
 *
 * Positions keep gaps of 1024 (D-09); when two neighbors are contiguous the
 * column is rebalanced first.
 */
class MoveTask
{
    /**
     * @param  int|null  $beforeId  Task that ends up right above the moved one.
     * @param  int|null  $afterId  Task that ends up right below the moved one.
     */
    public function handle(Task $task, User $actor, ProjectStatus $status, ?int $beforeId, ?int $afterId): void
    {
        DB::transaction(function () use ($task, $actor, $status, $beforeId, $afterId): void {
            $column = fn () => Task::query()
                ->where('project_id', $task->project_id)
                ->whereNull('parent_id')
                ->where('status_id', $status->id)
                ->whereKeyNot($task->id);

            $position = $this->positionBetween($column(), $beforeId, $afterId);

            if ($position === null) {
                $this->rebalance($column());
                $position = $this->positionBetween($column(), $beforeId, $afterId) ?? 0;
            }

            $oldStatus = $task->status;
            $statusChanged = $task->status_id !== $status->id;

            $task->forceFill(['status_id' => $status->id, 'position' => $position])->save();

            if ($statusChanged) {
                TaskActivity::record($task, $actor, TaskActivityEvent::StatusChanged, [
                    'status' => [
                        'old' => $oldStatus ? ['id' => $oldStatus->id, 'name' => $oldStatus->name] : null,
                        'new' => ['id' => $status->id, 'name' => $status->name],
                    ],
                ]);
            }
        });
    }

    /**
     * @param  Builder<Task>  $column
     */
    private function positionBetween(Builder $column, ?int $beforeId, ?int $afterId): ?int
    {
        $before = $beforeId !== null ? (clone $column)->whereKey($beforeId)->value('position') : null;
        $after = $afterId !== null ? (clone $column)->whereKey($afterId)->value('position') : null;

        return match (true) {
            $before !== null && $after !== null => $after - $before > 1 ? intdiv($before + $after, 2) : null,
            $before !== null => $before + 1024,
            $after !== null => $after - 1024,
            default => ((int) (clone $column)->max('position')) + 1024,
        };
    }

    /**
     * @param  Builder<Task>  $column
     */
    private function rebalance(Builder $column): void
    {
        $column->orderBy('position')->orderBy('id')->pluck('id')
            ->each(fn (int $id, int $index) => Task::query()->whereKey($id)->update(['position' => ($index + 1) * 1024]));
    }
}
