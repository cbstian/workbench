<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Impersonation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Starts and stops impersonation (super admin seeing the app as a user or a client).
 */
class ImpersonationController extends Controller
{
    public function __construct(private Impersonation $impersonation) {}

    public function store(User $user): RedirectResponse
    {
        Gate::authorize('impersonate', $user);

        $this->impersonation->start(request()->user(), $user);

        return redirect()->route('dashboard');
    }

    public function destroy(): RedirectResponse
    {
        abort_unless($this->impersonation->isActive(), 403);

        $returnRoute = $this->impersonation->returnRoute();
        $impersonator = $this->impersonation->stop();

        return $impersonator !== null
            ? redirect()->route($returnRoute)
            : redirect()->route('login');
    }
}
