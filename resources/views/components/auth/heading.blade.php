{{-- Title and short description at the top of every auth screen. --}}
@props([
    'title',
    'description' => null,
])

<div {{ $attributes->class('mb-8 space-y-1.5') }}>
    <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ $title }}</h1>
    @if ($description)
        <p class="text-sm text-ink-muted">{{ $description }}</p>
    @endif
</div>
