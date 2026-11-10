{{--
    Panel sidebar. Fixed on desktop, off-canvas on mobile (driven by the
    `sidebarOpen` Alpine state declared in layouts/app.blade.php).

    Add new sections with <x-app.nav-item>; keep labels in Spanish and route
    names in English.
--}}
<div>
    {{-- Mobile backdrop --}}
    <div x-cloak
         x-show="sidebarOpen"
         x-transition.opacity
         x-on:click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-black/30 md:hidden"></div>

    <aside x-bind:class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-50 flex w-60 -translate-x-full flex-col border-r border-line bg-sidebar transition-transform duration-200 ease-out md:translate-x-0"
           aria-label="Navegación principal">
        <div class="flex h-12 items-center px-3">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex min-w-0 items-center gap-2 rounded-md px-1.5 py-1 text-[15px] transition-colors hover:bg-hover">
                <x-brand.logo mark-class="size-5" />
            </a>
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto px-2 py-3">
            <div class="space-y-0.5">
                <x-app.nav-item :href="route('dashboard')" icon="home" :active="request()->routeIs('dashboard')">
                    Inicio
                </x-app.nav-item>
            </div>

            <div class="space-y-0.5">
                <p class="px-2 pb-1 text-xs font-medium text-ink-subtle">Espacio de trabajo</p>
                <x-app.nav-item :href="route('projects.index')" icon="folder" :active="request()->routeIs('projects.*')">
                    Proyectos
                </x-app.nav-item>
            </div>
        </nav>

        <div class="border-t border-line px-4 py-3 text-xs text-ink-subtle">
            {{ config('app.name') }} · {{ now()->year }}
        </div>
    </aside>
</div>
