{{--
    Page title block (Notion-style large heading with an optional description
    and actions slot on the right).
--}}
@props([
    'title',
    'description' => null,
])

<header {{ $attributes->class('flex flex-wrap items-end justify-between gap-4') }}>
    <div class="space-y-1">
        <h1 class="text-3xl font-bold tracking-tight text-ink">{{ $title }}</h1>
        @if ($description)
            <p class="text-sm text-ink-muted first-letter:uppercase">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex items-center gap-2">{{ $actions }}</div>
    @endisset
</header>
