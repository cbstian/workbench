{{--
    Base HTML document shared by every layout.

    - Applies the stored/system theme before first paint (<x-theme.init />).
    - Loads self-hosted fonts (@fonts), TallStackUI, Livewire and Vite assets.
    - Keeps the `dark` class on <html> in sync through TallStackUI's darkTheme store.
    - Renders the user's color palette as data-palette (guests get the default),
      see resources/css/theme/palettes/index.css.
    - Optional `head` slot for layout-specific <head> content (e.g. sidebar state).

    Usage: <x-layouts::base title="Página">...</x-layouts::base>
--}}
@props([
    'title' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      data-palette="{{ (auth()->user()?->palette ?? \App\Enums\ColorPalette::default())->value }}"
      x-data="tallstackui_darkTheme({ default: 'system' })"
      x-bind:class="{ 'dark': darkTheme }">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ filled($title) ? $title.' · '.config('app.name') : config('app.name') }}</title>

        <x-theme.init />
        {{ $head ?? '' }}
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
