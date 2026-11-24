<?php

namespace App\Actions\Board;

use App\Enums\BoardEntryStatus;
use App\Models\BoardEntry;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Writes a short note on a project day of the shared board (E11).
 *
 * The note can be stored already done, to log work that was not planned.
 * A linked task must belong to the same project.
 */
class CreateBoardEntry
{
    public function handle(Project $project, User $author, Carbon $day, string $body, BoardEntryStatus $status, ?Task $task = null): BoardEntry
    {
        return DB::transaction(function () use ($project, $author, $day, $body, $status, $task): BoardEntry {
            $position = (int) BoardEntry::query()
                ->where('project_id', $project->id)
                ->where('occurs_on', $day->toDateString())
                ->max('position');

            return $project->boardEntries()->create([
                'task_id' => $task?->id,
                'occurs_on' => $day->toDateString(),
                'body' => $body,
                'status' => $status,
                'position' => $position + 1024,
                'created_by_id' => $author->id,
                'completed_at' => $status === BoardEntryStatus::Done ? now() : null,
                'completed_by_id' => $status === BoardEntryStatus::Done ? $author->id : null,
            ]);
        });
    }
}
