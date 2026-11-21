<?php

namespace App\Support;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * The workspace the signed-in user is working in (F-02: «trabaja en uno a la vez»).
 *
 * The choice is kept in the session. When it is missing or no longer visible
 * (deleted, membership removed), it falls back to the first visible
 * workspace: owned ones first, then by name.
 */
class CurrentWorkspace
{
    public const string SESSION_KEY = 'current_workspace_id';

    private ?Workspace $resolved = null;

    private bool $isResolved = false;

    /**
     * Current workspace, or null when the user has none.
     */
    public function get(): ?Workspace
    {
        if ($this->isResolved) {
            return $this->resolved;
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        $visible = Workspace::query()->visibleTo($user);
        $workspace = Session::has(self::SESSION_KEY)
            ? (clone $visible)->whereKey(Session::get(self::SESSION_KEY))->first()
            : null;

        $workspace ??= $visible
            ->orderByRaw('case when account_id = ? then 0 else 1 end', [$user->ownedAccount?->id ?? 0])
            ->orderBy('name')
            ->first();

        if ($workspace !== null) {
            Session::put(self::SESSION_KEY, $workspace->id);
        }

        $this->isResolved = true;

        return $this->resolved = $workspace;
    }

    /**
     * Work in another workspace. The caller authorizes access first.
     */
    public function switchTo(Workspace $workspace): void
    {
        Session::put(self::SESSION_KEY, $workspace->id);

        $this->resolved = $workspace;
        $this->isResolved = true;
    }

    /**
     * Drop the choice so the next read falls back to the first visible workspace.
     */
    public function forget(): void
    {
        Session::forget(self::SESSION_KEY);

        $this->resolved = null;
        $this->isResolved = false;
    }
}
