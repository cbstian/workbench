{{--
    Emoji icon picker (pages, projects). A square button shows the current
    emoji; clicking it opens a searchable emoji grid in Spanish
    (emoji-picker-element, lazy-loaded by resources/js/modules/emoji-picker.js).
    The value is a plain emoji string, so it fits the existing `icon` columns.

    Props:
    - label:       field label (default "Icono").
    - placeholder: emoji shown faded when there is no value (default 📄).

    Bind it like any field, inside a Livewire component:

        <x-ui.icon-picker wire:model="form.icon" placeholder="📁" />
--}}
@props([
    'label' => 'Icono',
    'placeholder' => '📄',
])

@php($property = $attributes->wire('model')->value())

<div x-data="wbEmojiPicker($wire.entangle(@js($property)))"
     x-on:keydown.escape.stop="close()"
     {{ $attributes->whereDoesntStartWith('wire:model')->class('relative') }}>
    @if ($label)
        <x-label :label="$label" />
    @endif

    <button type="button" x-ref="trigger" x-on:click="toggle()"
            class="mt-1 flex size-9 items-center justify-center rounded-md border border-line-strong bg-surface text-xl leading-none shadow-xs transition hover:bg-hover focus:ring-2 focus:ring-ring focus:outline-none"
            x-bind:aria-expanded="open" aria-haspopup="dialog" aria-label="Elegir icono">
        <span x-show="value" x-text="value" aria-hidden="true"></span>
        <span x-show="! value" class="opacity-40 grayscale" aria-hidden="true">{{ $placeholder }}</span>
    </button>

    {{-- Rendered in place, not teleported: a click inside must not count as a click outside a slide. --}}
    <div x-cloak x-show="open" x-transition.opacity.duration.100ms
         x-on:click.outside="if (! $refs.trigger.contains($event.target)) close()"
         class="absolute top-full left-0 z-50 mt-1.5 w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-lg border border-edge bg-elevated shadow-lg"
         role="dialog" aria-label="Elegir icono">
        <div x-ref="host" wire:ignore></div>

        <div x-show="loading" class="flex h-80 items-center justify-center text-sm text-ink-muted">Cargando emojis…</div>

        <div class="flex items-center justify-between border-t border-line px-3 py-2">
            <span class="text-xs text-ink-subtle">Busca en español: «carpeta», «libro»…</span>
            <button type="button" x-on:click="clear()" x-show="value"
                    class="rounded px-2 py-1 text-xs text-ink-muted hover:bg-hover hover:text-ink">
                Quitar
            </button>
        </div>
    </div>

    <x-error :property="$property" />
</div>
