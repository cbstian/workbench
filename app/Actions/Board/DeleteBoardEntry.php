<?php

namespace App\Actions\Board;

use App\Models\BoardEntry;

/**
 * Removes a board note. Notes are day-level marks, not trash items (E11):
 * deleting one does not send it to the workspace trash.
 */
class DeleteBoardEntry
{
    public function handle(BoardEntry $entry): void
    {
        $entry->delete();
    }
}
