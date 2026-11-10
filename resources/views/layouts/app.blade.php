{{--
    Authenticated panel layout (Notion-like): fixed sidebar + slim topbar.

    Livewire full-page components render here automatically (config
    livewire.component_layout = layouts::app); set the page title with the
    #[Title('...')] attribute on the component class.

    Blade usage: <x-layouts::app title="Página">...</x-layouts::app>
--}}
@props([
    'title' => null,
])

<x-layouts::base :title="$title">
    <div x-data="{ sidebarOpen: false }"
         x-on:keydown.escape.window="sidebarOpen = false"
         class="min-h-dvh">
        <x-app.sidebar />

        <div class="flex min-h-dvh flex-col md:pl-60">
            <x-app.topbar :title="$title" />

            <main class="mx-auto w-full max-w-5xl flex-1 px-5 pt-8 pb-16 sm:px-10 lg:px-16">
                {{ $slot }}
            </main>
        </div>
    </div>
</x-layouts::base>
