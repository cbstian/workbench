<?php

namespace App\Livewire\Concerns;

use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Livewire\Attributes\Computed;

/**
 * Gives a page component the workspace the user is working in.
 *
 * Routes that need one use the `workspace` middleware, so pages can rely on it.
 */
trait WithCurrentWorkspace
{
    #[Computed]
    public function workspace(): Workspace
    {
        $workspace = app(CurrentWorkspace::class)->get();

        abort_if($workspace === null, 404);

        return $workspace;
    }
}
