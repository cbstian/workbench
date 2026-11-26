{{--
    One selectable card of the palette selector (Ajustes → Apariencia).

    The miniature is wrapped in data-palette, so it is painted by the same
    palette CSS as the real panel (and follows light/dark) without repeating
    any color here. Extra attributes go to the radio input, e.g. wire:model.

    Usage: <x-theme.palette-option :palette="ColorPalette::Oceano" wire:model.live="palette" />
--}}
@props([
    'palette',
])

<label class="group block cursor-pointer">
    <input type="radio" value="{{ $palette->value }}" class="peer sr-only" {{ $attributes }} />

    <span class="block rounded-lg border border-line p-1.5 transition-colors group-hover:border-line-strong peer-checked:border-accent peer-checked:ring-2 peer-checked:ring-ring peer-focus-visible:ring-2 peer-focus-visible:ring-ring">
        <span data-palette="{{ $palette->value }}" class="flex h-20 overflow-hidden rounded-md border border-line bg-canvas" aria-hidden="true">
            <span class="flex w-1/3 flex-col gap-1.5 bg-sidebar p-2">
                <span class="h-1.5 w-3/4 rounded-full bg-ink-subtle/60"></span>
                <span class="h-1.5 w-full rounded-full bg-active"></span>
                <span class="h-1.5 w-2/3 rounded-full bg-ink-subtle/40"></span>
            </span>
            <span class="flex flex-1 flex-col gap-1.5 p-2">
                <span class="h-2 w-2/3 rounded-full bg-ink"></span>
                <span class="h-1.5 w-full rounded-full bg-line-strong"></span>
                <span class="mt-auto flex items-center gap-1">
                    <span class="h-3 w-8 rounded bg-primary-500"></span>
                    <span class="h-3 w-5 rounded bg-today"></span>
                </span>
            </span>
        </span>

        <span class="mt-2 block px-1 pb-0.5">
            <span class="block text-sm font-medium text-ink">{{ $palette->label() }}</span>
            <span class="block text-xs text-ink-muted">{{ $palette->description() }}</span>
        </span>
    </span>
</label>
