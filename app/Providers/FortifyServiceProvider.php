<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

/**
 * Configures Fortify for Workbench: login and password reset only.
 *
 * Registration, two-factor authentication and passkeys are disabled on
 * purpose (users are created from the console, see docs/flujo-canonico.md).
 */
class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        $this->authenticateActiveUsersOnly();
        $this->registerViews();
        $this->registerRateLimiters();
    }

    /**
     * Check the credentials and reject deactivated users (A-09). Deleted users
     * are excluded by soft deletes, so they fail as unknown credentials (A-10).
     */
    private function authenticateActiveUsersOnly(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::query()
                ->whereRaw('lower(email) = ?', [Str::lower((string) $request->input(Fortify::username()))])
                ->first();

            if ($user === null || ! Hash::check((string) $request->input('password'), $user->password)) {
                return null;
            }

            if ($user->deactivated_at !== null) {
                throw ValidationException::withMessages([Fortify::username() => __('auth.deactivated')]);
            }

            return $user;
        });
    }

    /**
     * Point every enabled Fortify screen to its Blade view.
     */
    private function registerViews(): void
    {
        Fortify::loginView(fn () => view('auth.login'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));
    }

    /**
     * Throttle login attempts per email and IP address.
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
