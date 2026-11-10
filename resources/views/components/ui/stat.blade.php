{{-- Small figure card used on the dashboard. --}}
@props([
    'label',
    'value',
    'hint' => null,
])

<div {{ $attributes->class('rounded-lg border border-line bg-surface p-4') }}>
    <p class="text-xs font-medium text-ink-muted">{{ $label }}</p>
    <p class="mt-2 font-display text-3xl font-semibold tracking-tight text-ink tabular-nums">{{ number_format($value, 0, ',', '.') }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-ink-subtle first-letter:uppercase">{{ $hint }}</p>
    @endif
</div>
