<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Impersonation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out a user whose access was deactivated while they had a session (A-09).
 *
 * Deleted users never reach here: soft deletes hide them from the user provider.
 * While a super admin impersonates a user who gets deactivated, the super
 * admin goes back to their own session instead.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->isActive() && app(Impersonation::class)->isActive()) {
            app(Impersonation::class)->stop();

            return redirect()->route('users.index');
        }

        if ($user instanceof User && ! $user->isActive()) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => __('auth.deactivated')]);
        }

        return $next($request);
    }
}
