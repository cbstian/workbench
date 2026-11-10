{{--
    Authenticated user's menu: identity, theme selector and logout.
    Logout posts to Fortify's /logout route.
--}}
@php($user = auth()->user())

<x-dropdown position="bottom-end">
    <x-slot:action>
        <button type="button"
                x-on:click="show = !show"
                class="flex items-center gap-2 rounded-md p-1 transition-colors hover:bg-hover"
                aria-label="Menú de usuario">
            <x-app.avatar :name="$user->name" />
        </button>
    </x-slot:action>

    <div class="px-3 pt-2 pb-3">
        <p class="truncate text-sm font-medium text-ink">{{ $user->name }}</p>
        <p class="truncate text-xs text-ink-muted">{{ $user->email }}</p>
    </div>

    <div class="border-t border-line px-3 py-3">
        <p class="mb-2 text-xs font-medium text-ink-subtle">Tema</p>
        <x-theme-switch block />
    </div>

    <form method="POST" action="{{ route('logout') }}" class="border-t border-line pt-1">
        @csrf
        <x-dropdown.items type="submit" icon="arrow-right-start-on-rectangle" text="Cerrar sesión" class="w-full" />
    </form>
</x-dropdown>
