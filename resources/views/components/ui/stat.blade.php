{{--
    Small figure card used on the dashboard.

    Props:
    - label, value, hint: the figure and its helper line.
    - href:  optional link; the whole card becomes clickable (wire:navigate).
    - tone:  "danger" paints the figure in the overdue color when value > 0.
--}}
@props([
    'label',
    'value',
    'hint' => null,
    'href' => null,
    'tone' => null,
])

@php($alert = $tone === 'danger' && $value > 0)

<{{ $href ? 'a' : 'div' }}
    @if ($href) href="{{ $href }}" wire:navigate @endif
    {{ $attributes->class([
        'block rounded-lg border bg-surface p-4',
        'border-overdue/40' => $alert,
        'border-line' => ! $alert,
        'transition-colors hover:bg-hover' => $href,
    ]) }}>
    <p @class(['text-xs font-medium', 'text-overdue' => $alert, 'text-ink-muted' => ! $alert])>{{ $label }}</p>
    <p @class(['mt-2 font-display text-3xl font-semibold tracking-tight tabular-nums', 'text-overdue' => $alert, 'text-ink' => ! $alert])>{{ number_format($value, 0, ',', '.') }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-ink-subtle first-letter:uppercase">{{ $hint }}</p>
    @endif
</{{ $href ? 'a' : 'div' }}>
