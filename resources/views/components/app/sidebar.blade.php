{{--
    Panel sidebar, rendered from config/navigation.php.

    - Desktop: fixed on the left; collapses completely (Notion-like) with the
      « button, the topbar button or Ctrl/Cmd + \. State: Alpine store `sidebar`.
    - Mobile: off-canvas panel over a backdrop.

    Do not add links here: register them in config/navigation.php.
--}}
@php
    $groups = config('navigation.groups', []);
    $footerItems = config('navigation.footer', []);
@endphp

<div>
    {{-- Mobile backdrop --}}
    <div x-cloak
         x-show="$store.sidebar.mobileOpen"
         x-transition.opacity
         x-on:click="$store.sidebar.mobileOpen = false"
         class="fixed inset-0 z-40 bg-black/30 md:hidden"></div>

    <aside x-bind:class="$store.sidebar.mobileOpen ? 'translate-x-0 shadow-xl' : '-translate-x-full'"
           x-on:keydown.escape.window="$store.sidebar.mobileOpen = false"
           class="group/sidebar fixed inset-y-0 left-0 z-50 flex w-60 -translate-x-full flex-col border-r border-line bg-sidebar transition-transform duration-200 ease-out motion-reduce:transition-none md:sidebar-expanded:translate-x-0 md:sidebar-collapsed:-translate-x-full"
           aria-label="Navegación principal">
        <div class="flex h-12 shrink-0 items-center justify-between gap-1 px-3">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex min-w-0 items-center gap-2 rounded-md px-1.5 py-1 text-[15px] transition-colors hover:bg-hover">
                <x-brand.logo mark-class="size-5" />
            </a>

            <button type="button"
                    x-on:click="$store.sidebar.toggle()"
                    class="inline-flex size-7 items-center justify-center rounded-md text-ink-subtle transition hover:bg-hover hover:text-ink md:opacity-0 md:group-hover/sidebar:opacity-100 md:focus-visible:opacity-100"
                    title="Cerrar barra lateral (Ctrl + \)"
                    aria-label="Cerrar barra lateral">
                <x-icon icon="x-mark" class="size-4 md:hidden" />
                <x-icon icon="chevron-double-left" class="hidden size-4 md:block" />
            </button>
        </div>

        <nav class="flex-1 space-y-5 overflow-y-auto px-2 py-2">
            @foreach ($groups as $group)
                <x-app.nav-group :label="$group['label'] ?? null" :items="$group['items'] ?? []" />
            @endforeach
        </nav>

        @if ($footerItems !== [])
            <div class="border-t border-line px-2 py-2">
                <x-app.nav-group :items="$footerItems" />
            </div>
        @endif
    </aside>
</div>
