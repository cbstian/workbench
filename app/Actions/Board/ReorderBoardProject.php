<?php

namespace App\Actions\Board;

use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Moves a project among its neighbors on the shared board (E11, D-09).
 *
 * The order is the workspace's, not a personal preference. Positions keep
 * gaps of 1024 and the column is rebalanced when two neighbors touch.
 */
class ReorderBoardProject
{
    /**
     * @param  int|null  $beforeId  Project that ends up just above the moved one.
     * @param  int|null  $afterId  Project that ends up just below the moved one.
     */
    public function handle(Project $project, ?int $beforeId, ?int $afterId): void
    {
        DB::transaction(function () use ($project, $beforeId, $afterId): void {
            $column = fn () => Project::query()
                ->where('workspace_id', $project->workspace_id)
                ->whereKeyNot($project->id);

            $position = $this->positionBetween($column(), $beforeId, $afterId);

            if ($position === null) {
                $this->rebalance($column());
                $position = $this->positionBetween($column(), $beforeId, $afterId) ?? 0;
            }

            $project->forceFill(['board_position' => $position])->save();
        });
    }

    /**
     * @param  Builder<Project>  $column
     */
    private function positionBetween(Builder $column, ?int $beforeId, ?int $afterId): ?int
    {
        $before = $beforeId !== null ? (clone $column)->whereKey($beforeId)->value('board_position') : null;
        $after = $afterId !== null ? (clone $column)->whereKey($afterId)->value('board_position') : null;

        return match (true) {
            $before !== null && $after !== null => $after - $before > 1 ? intdiv($before + $after, 2) : null,
            $before !== null => $before + 1024,
            $after !== null => $after - 1024,
            default => ((int) (clone $column)->max('board_position')) + 1024,
        };
    }

    /**
     * @param  Builder<Project>  $column
     */
    private function rebalance(Builder $column): void
    {
        $column->orderBy('board_position')->orderBy('id')->pluck('id')
            ->each(fn (int $id, int $index) => Project::query()->whereKey($id)->update(['board_position' => ($index + 1) * 1024]));
    }
}
