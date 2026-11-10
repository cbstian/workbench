{{--
    Panel topbar: mobile menu button, current page title, theme toggle and
    user menu. Stays sticky and translucent like Notion's header.
--}}
@props([
    'title' => null,
])

<header class="sticky top-0 z-30 flex h-12 items-center gap-2 bg-canvas/85 px-3 backdrop-blur supports-[backdrop-filter]:bg-canvas/70 sm:px-4">
    <button type="button"
            x-on:click="sidebarOpen = true"
            class="inline-flex size-8 items-center justify-center rounded-md text-ink-muted transition-colors hover:bg-hover hover:text-ink md:hidden"
            aria-label="Abrir menú">
        <x-icon icon="bars-3" class="size-5" />
    </button>

    <p class="min-w-0 flex-1 truncate text-sm text-ink">{{ $title }}</p>

    <div class="flex items-center gap-1">
        <x-theme.toggle />
        <x-app.user-menu />
    </div>
</header>
