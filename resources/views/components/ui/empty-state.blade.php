{{--
    Placeholder for empty lists and sections.

    Props:
    - icon:        Heroicons name (default: inbox).
    - title:       short headline.
    - description: optional helper text.

    Slot: optional call to action (e.g. a button to create the first item).

    Usage:
        <x-ui.empty-state title="Aún no hay proyectos" description="Crea el primero para empezar.">
            <x-button text="Nuevo proyecto" sm />
        </x-ui.empty-state>
--}}
@props([
    'icon' => 'inbox',
    'title',
    'description' => null,
])

<div {{ $attributes->class('flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-line-strong px-6 py-12 text-center') }}>
    <x-icon :icon="$icon" class="size-6 text-ink-subtle" />
    <p class="text-sm font-medium text-ink">{{ $title }}</p>
    @if ($description)
        <p class="max-w-sm text-sm text-ink-muted">{{ $description }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-2">{{ $slot }}</div>
    @endif
</div>
