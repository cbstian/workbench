{{--
    A titled block inside a page (Notion-like: small muted heading, no box).
    Wrap the content in a TallStackUI <x-card> only when it needs a surface.

    Props:
    - title:       section heading (optional).
    - description: optional helper line under the heading.

    Slots:
    - actions: links or small buttons aligned to the right of the heading.

    Usage:
        <x-ui.section title="Actividad reciente">
            <x-slot:actions><a href="…">Ver todos</a></x-slot:actions>
            …
        </x-ui.section>
--}}
@props([
    'title' => null,
    'description' => null,
])

<section {{ $attributes->class('space-y-3') }}>
    @if ($title || isset($actions))
        <div class="flex flex-wrap items-end justify-between gap-2">
            <div class="min-w-0">
                @if ($title)
                    <h2 class="text-sm font-medium text-ink-muted">{{ $title }}</h2>
                @endif
                @if ($description)
                    <p class="text-xs text-ink-subtle">{{ $description }}</p>
                @endif
            </div>

            @isset($actions)
                <div class="flex items-center gap-2 text-sm">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    {{ $slot }}
</section>
