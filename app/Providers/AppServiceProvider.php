<?php

namespace App\Providers;

use App\Http\Middleware\EnsureUserIsActive;
use App\Models\User;
use App\Support\CurrentWorkspace;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(CurrentWorkspace::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->defineNavigationAbilities();

        Livewire::addPersistentMiddleware([EnsureUserIsActive::class]);
    }

    /**
     * Abilities without arguments, used by config/navigation.php (`can`).
     */
    private function defineNavigationAbilities(): void
    {
        Gate::define('manage-account', fn (User $user): bool => $user->isSuperAdmin());

        Gate::define('manage-workspace', function (User $user): bool {
            $workspace = app(CurrentWorkspace::class)->get();

            return $workspace !== null && $user->ownsWorkspace($workspace);
        });

        Gate::define('view-projects', function (User $user): bool {
            $workspace = app(CurrentWorkspace::class)->get();

            return $workspace !== null
                && ($user->ownsWorkspace($workspace) || $user->roleIn($workspace)?->isInternal() === true);
        });

        Gate::define('view-members', function (User $user): bool {
            $workspace = app(CurrentWorkspace::class)->get();

            return $workspace !== null && $user->can('viewMembers', $workspace);
        });

        Gate::define('view-trash', function (User $user): bool {
            $workspace = app(CurrentWorkspace::class)->get();

            return $workspace !== null && $user->can('viewTrash', $workspace);
        });
    }
}
