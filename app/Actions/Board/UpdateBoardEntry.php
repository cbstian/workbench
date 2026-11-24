<?php

namespace App\Actions\Board;

use App\Models\BoardEntry;
use App\Models\Task;

/**
 * Changes the text of a board note and the task it points at (E11).
 *
 * Status is changed through MarkBoardEntry, so a text edit does not
 * accidentally complete or reopen the note.
 */
class UpdateBoardEntry
{
    public function handle(BoardEntry $entry, string $body, ?Task $task): void
    {
        $entry->update([
            'body' => $body,
            'task_id' => $task?->id,
        ]);
    }
}
