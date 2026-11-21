{{--
    Authenticated panel layout (Notion-like): collapsible sidebar + slim topbar
    + full-width content area.

    Livewire full-page components render here automatically (config
    livewire.component_layout = layouts::app); set the page title with the
    #[Title('...')] attribute on the component class.

    Every page wraps its content in <x-ui.page> (width and vertical rhythm),
    see docs/arquitectura/interfaz.md.

    Blade usage: <x-layouts::app title="Página">...</x-layouts::app>
--}}
@props([
    'title' => null,
])

<x-layouts::base :title="$title">
    <x-slot:head>
        <x-app.sidebar-init />
    </x-slot:head>

    <x-app.sidebar />

    <div class="flex min-h-dvh flex-col transition-[padding] duration-200 ease-out motion-reduce:transition-none md:sidebar-expanded:pl-60">
        <x-app.topbar :title="$title" />

        <main id="content" class="flex-1 px-5 pt-6 pb-16 sm:px-8 lg:px-12 xl:px-16">
            {{ $slot }}
        </main>
    </div>

    <x-toast />
    <x-dialog />
</x-layouts::base>
