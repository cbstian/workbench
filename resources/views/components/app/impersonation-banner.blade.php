{{--
    Floating bar shown while a super admin sees the app as another user
    (App\Support\Impersonation). Always visible; «Volver a mi perfil» ends it.
--}}
@php
    $impersonation = app(\App\Support\Impersonation::class);
    $impersonator = $impersonation->impersonator();
    $user = auth()->user();
    $workspace = app(\App\Support\CurrentWorkspace::class)->get();
    $role = $workspace && $user ? $user->roleIn($workspace) : null;
@endphp

@if ($impersonator && $user)
    <div role="status" aria-live="polite"
         class="fixed inset-x-3 bottom-3 z-[60] mx-auto flex max-w-2xl flex-wrap items-center gap-x-3 gap-y-2 rounded-xl bg-ink px-4 py-2.5 text-sm text-canvas shadow-lg sm:inset-x-0">
        <x-icon icon="eye" class="size-5 shrink-0" />
        <p class="min-w-0 flex-1">
            Estás viendo Workbench como <strong class="font-semibold">{{ $user->name }}</strong>@if ($role) <span class="opacity-75">({{ $role->label() }})</span>@endif.
            <span class="opacity-75">Lo que hagas queda a su nombre.</span>
        </p>
        <form method="POST" action="{{ route('impersonation.stop') }}">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="inline-flex h-8 items-center gap-1.5 rounded-md bg-canvas px-3 text-sm font-medium text-ink transition-opacity hover:opacity-90">
                <x-icon icon="arrow-uturn-left" class="size-4" />
                Volver a mi perfil
            </button>
        </form>
    </div>
@endif
