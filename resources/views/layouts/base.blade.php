{{--
    Base HTML document shared by every layout.

    - Applies the stored/system theme before first paint (<x-theme.init />).
    - Loads self-hosted fonts (@fonts), TallStackUI, Livewire and Vite assets.
    - Keeps the `dark` class on <html> in sync through TallStackUI's darkTheme store.

    Usage: <x-layouts::base title="Página">...</x-layouts::base>
--}}
@props([
    'title' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="tallstackui_darkTheme({ default: 'system' })"
      x-bind:class="{ 'dark': darkTheme }">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ filled($title) ? $title.' · '.config('app.name') : config('app.name') }}</title>

        <x-theme.init />
        <x-meta.favicons />

        @fonts
        <tallstackui:script />
        @livewireStyles
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body {{ $attributes->class('min-h-dvh bg-canvas text-ink') }}>
        {{ $slot }}

        @livewireScripts
    </body>
</html>
