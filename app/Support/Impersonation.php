<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Lets a super admin sign in as a user of their account to see what that user
 * sees, and come back to their own session.
 *
 * The super admin's id and current workspace are kept in the session while
 * impersonating; everything done meanwhile is done as the impersonated user.
 */
class Impersonation
{
    public const string IMPERSONATOR_KEY = 'impersonator_id';

    public const string IMPERSONATOR_WORKSPACE_KEY = 'impersonator_workspace_id';

    public const string RETURN_ROUTE_KEY = 'impersonator_return_route';

    public function __construct(private CurrentWorkspace $currentWorkspace) {}

    public function isActive(): bool
    {
        return Session::has(self::IMPERSONATOR_KEY);
    }

    /**
     * The super admin behind the current session, while impersonating.
     */
    public function impersonator(): ?User
    {
        return $this->isActive() ? User::query()->find(Session::get(self::IMPERSONATOR_KEY)) : null;
    }

    /**
     * Sign in as the target. The caller authorizes it first (UserPolicy::impersonate).
     *
     * `$returnRoute` names the page the super admin goes back to when it ends.
     */
    public function start(User $impersonator, User $target, string $returnRoute = 'users.index'): void
    {
        Session::put(self::IMPERSONATOR_KEY, $impersonator->id);
        Session::put(self::IMPERSONATOR_WORKSPACE_KEY, $this->currentWorkspace->get()?->id);
        Session::put(self::RETURN_ROUTE_KEY, $returnRoute);

        $this->currentWorkspace->forget();
        Auth::guard('web')->login($target);
        Session::regenerate();
    }

    /**
     * Route of the page the super admin started from (users or clients).
     */
    public function returnRoute(): string
    {
        return Session::get(self::RETURN_ROUTE_KEY, 'users.index');
    }

    /**
     * Go back to the super admin's session and workspace.
     */
    public function stop(): ?User
    {
        $impersonator = $this->impersonator();
        $workspaceId = Session::get(self::IMPERSONATOR_WORKSPACE_KEY);

        Session::forget([self::IMPERSONATOR_KEY, self::IMPERSONATOR_WORKSPACE_KEY, self::RETURN_ROUTE_KEY]);
        $this->currentWorkspace->forget();

        if ($impersonator === null) {
            Auth::guard('web')->logout();

            return null;
        }

        Auth::guard('web')->login($impersonator);
        Session::regenerate();

        if ($workspaceId !== null) {
            Session::put(CurrentWorkspace::SESSION_KEY, $workspaceId);
        }

        return $impersonator;
    }
}
