<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\CurrentWorkspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pages inside a workspace need one: a super admin without workspaces is sent
 * to create the first one; anybody else goes back home.
 */
class EnsureCurrentWorkspace
{
    public function __construct(private CurrentWorkspace $currentWorkspace) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->currentWorkspace->get() !== null) {
            return $next($request);
        }

        $user = $request->user();

        return $user instanceof User && $user->isSuperAdmin()
            ? redirect()->route('workspaces.index')
            : redirect()->route('dashboard');
    }
}
