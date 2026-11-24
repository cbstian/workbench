<?php

namespace App\Actions\Board;

use App\Enums\BoardEntryStatus;
use App\Models\BoardEntry;
use App\Models\User;

/**
 * Marks a board note done or returns it to planned (E11).
 *
 * Done notes record who closed them and when. Planned notes are not overdue
 * until their day is before today.
 */
class MarkBoardEntry
{
    public function handle(BoardEntry $entry, User $actor, BoardEntryStatus $status): void
    {
        $done = $status === BoardEntryStatus::Done;

        $entry->update([
            'status' => $status,
            'completed_at' => $done ? ($entry->completed_at ?? now()) : null,
            'completed_by_id' => $done ? ($entry->completed_by_id ?? $actor->id) : null,
        ]);
    }
}
