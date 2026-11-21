{{--
    Page title block (Notion-style large heading) with optional breadcrumbs,
    icon (emoji), description and an actions slot on the right.

    Props:
    - title:       page heading.
    - description: optional line below the title.
    - icon:        optional emoji shown before the title (e.g. a project icon).
    - breadcrumbs: optional list of ['label' => …, 'link' => …] (last item = current page).

    Slots:
    - actions: buttons aligned to the right (primary action last).

    Usage:
        <x-ui.page-header title="Proyectos" description="…" :breadcrumbs="[['label' => 'Inicio', 'link' => route('dashboard')], ['label' => 'Proyectos']]">
            <x-slot:actions>
                <x-button icon="plus" text="Nuevo proyecto" sm />
            </x-slot:actions>
        </x-ui.page-header>
--}}
@props([
    'title',
    'description' => null,
    'icon' => null,
    'breadcrumbs' => [],
])

<header {{ $attributes->class('space-y-3') }}>
    @if ($breadcrumbs !== [])
        <nav aria-label="Ruta de navegación">
            <ol class="flex flex-wrap items-center gap-1 text-xs text-ink-muted">
                @foreach ($breadcrumbs as $crumb)
                    <li class="flex items-center gap-1">
                        @if (! $loop->first)
                            <span class="text-ink-subtle" aria-hidden="true">/</span>
                        @endif

                        @if (isset($crumb['link']) && ! $loop->last)
                            <a href="{{ $crumb['link'] }}" wire:navigate class="rounded px-1 py-0.5 transition-colors hover:bg-hover hover:text-ink">{{ $crumb['label'] }}</a>
                        @else
                            <span class="px-1 py-0.5 text-ink" @if ($loop->last) aria-current="page" @endif>{{ $crumb['label'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    @endif

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0 space-y-1">
            <h1 class="flex items-center gap-3 text-3xl font-bold tracking-tight text-ink">
                @if ($icon)
                    <span class="leading-none" aria-hidden="true">{{ $icon }}</span>
                @endif
                <span class="min-w-0 break-words">{{ $title }}</span>
            </h1>
            @if ($description)
                <p class="text-sm text-ink-muted first-letter:uppercase">{{ $description }}</p>
            @endif
        </div>

        @isset($actions)
            <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>
</header>
