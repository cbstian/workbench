{{--
    Layout for unauthenticated screens (login, password recovery).
    Centered single column with the logo and the theme toggle on top.

    Usage: <x-layouts::guest title="Iniciar sesión">...</x-layouts::guest>
--}}
@props([
    'title' => null,
])

<x-layouts::base :title="$title">
    <div class="flex min-h-dvh flex-col">
        <header class="flex items-center justify-between px-6 py-5">
            <a href="{{ route('home') }}" class="rounded-md text-[15px]" aria-label="{{ config('app.name') }}">
                <x-brand.logo mark-class="size-6" />
            </a>
            <x-theme.toggle />
        </header>

        <main class="flex flex-1 justify-center px-6 pt-[10vh] pb-16">
            <div class="w-full max-w-sm">
                {{ $slot }}
            </div>
        </main>
    </div>
</x-layouts::base>
