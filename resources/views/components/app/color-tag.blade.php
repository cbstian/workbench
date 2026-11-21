{{--
    A status, priority or label: colored dot and name.

    Props:
    - color:   hex color of the value (optional).
    - text:    name.
    - initial: marks the value new tasks take (RN-22).
--}}
@props([
    'color' => null,
    'text',
    'initial' => false,
])

<span {{ $attributes->class('inline-flex h-6 items-center gap-1.5 rounded-md border border-line bg-surface px-2 text-xs text-ink') }}>
    <span class="size-2 shrink-0 rounded-full bg-ink-subtle" @if ($color) style="background-color: {{ $color }}" @endif aria-hidden="true"></span>
    <span class="first-letter:uppercase">{{ $text }}</span>
    @if ($initial)
        <span class="text-ink-subtle">· inicial</span>
    @endif
</span>
