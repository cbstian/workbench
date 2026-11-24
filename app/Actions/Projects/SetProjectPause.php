<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\User;

/**
 * Pauses or resumes a project (E11).
 *
 * Pausing is reversible. It is not archiving and it does not send the project
 * to the trash. A paused project stays on the board, struck through.
 */
class SetProjectPause
{
    public function handle(Project $project, User $actor, bool $paused): void
    {
        if ($paused === $project->isPaused()) {
            return;
        }

        $project->forceFill($paused
            ? ['paused_at' => now(), 'paused_by_id' => $actor->id]
            : ['paused_at' => null, 'paused_by_id' => null],
        )->save();
    }
}
