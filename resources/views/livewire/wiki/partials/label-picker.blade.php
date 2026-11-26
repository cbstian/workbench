{{--
    Wiki labels of the page being edited (App\Livewire\Wiki\PageShow): chosen
    labels as colored chips, and a popover to search, pick or create one.
    Typing a name that does not exist and pressing Enter creates it.
--}}
<div x-data="{
        open: false,
        search: $wire.entangle('newLabel'),
        get term() { return (this.search ?? '').trim().toLowerCase() },
        matches(name) { return name.includes(this.term) },
        get exists() { return this.term !== '' && [...this.$refs.options.querySelectorAll('[data-name]')].some((option) => option.dataset.name === this.term) },
        focusSearch() { setTimeout(() => this.$refs.search.focus(), 30) },
        toggle() { this.open = ! this.open; if (this.open) { this.focusSearch() } },
        close() { this.open = false; this.search = '' },
        submit() { if (this.term !== '') { $wire.addLabel().then(() => this.focusSearch()) } },
     }"
     x-on:keydown.escape.stop="close()">
    <x-label label="Etiquetas" />

    <div class="mt-1 flex min-h-9 flex-wrap items-center gap-1.5 rounded-md border border-line-strong bg-surface px-2 py-1.5 shadow-xs">
        @foreach ($this->selectedLabels as $selectedLabel)
            <span wire:key="selected-label-{{ $selectedLabel['value'] }}"
                  class="inline-flex h-6 items-center gap-1.5 rounded-md pr-0.5 pl-2 text-xs font-medium text-ink"
                  style="background-color: color-mix(in srgb, {{ $selectedLabel['color'] ?? 'var(--wb-ink-subtle)' }} 18%, transparent)">
                <span class="size-2 shrink-0 rounded-full" style="background-color: {{ $selectedLabel['color'] ?? 'var(--wb-ink-subtle)' }}" aria-hidden="true"></span>
                <span class="first-letter:uppercase">{{ $selectedLabel['label'] }}</span>
                <button type="button" wire:click="toggleLabel({{ $selectedLabel['value'] }})"
                        class="inline-flex size-5 items-center justify-center rounded text-ink-muted hover:bg-hover hover:text-ink"
                        aria-label="Quitar «{{ $selectedLabel['label'] }}»">
                    <x-icon icon="x-mark" class="size-3" />
                </button>
            </span>
        @endforeach

        <button type="button" x-ref="trigger" x-on:click="toggle()" x-bind:aria-expanded="open"
                class="inline-flex h-6 items-center gap-1 rounded-md px-1.5 text-xs text-ink-muted hover:bg-hover hover:text-ink">
            <x-icon icon="plus" class="size-3.5" />
            {{ $this->selectedLabels === [] ? 'Agregar etiqueta' : 'Etiqueta' }}
        </button>
    </div>

    <div x-cloak x-show="open" x-transition.opacity.duration.100ms
         x-anchor.bottom-start.offset.6="$refs.trigger"
         x-on:click.outside="close()"
         class="z-50 w-72 max-w-[calc(100vw-2rem)] overflow-hidden rounded-lg border border-edge bg-elevated shadow-lg">
        <div class="border-b border-line p-2">
            <input type="text" x-ref="search" x-model="search" maxlength="64"
                   x-on:keydown.enter.prevent="submit()"
                   placeholder="Buscar o crear etiqueta…"
                   class="w-full rounded-md border-0 bg-transparent px-2 py-1 text-sm text-ink placeholder:text-ink-subtle focus:ring-0" />
        </div>

        <ul x-ref="options" class="max-h-60 overflow-y-auto p-1" role="listbox" aria-label="Etiquetas de la wiki">
            @foreach ($this->labelOptions as $labelOption)
                @php($isSelected = in_array($labelOption['value'], $labelIds, true))
                <li wire:key="label-option-{{ $labelOption['value'] }}" data-name="{{ mb_strtolower($labelOption['label']) }}"
                    x-show="matches(@js(mb_strtolower($labelOption['label'])))">
                    <button type="button" wire:click="toggleLabel({{ $labelOption['value'] }})" role="option" aria-selected="{{ $isSelected ? 'true' : 'false' }}"
                            class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm text-ink hover:bg-hover">
                        <span class="size-2.5 shrink-0 rounded-full bg-ink-subtle" @if ($labelOption['color']) style="background-color: {{ $labelOption['color'] }}" @endif aria-hidden="true"></span>
                        <span class="min-w-0 flex-1 truncate first-letter:uppercase">{{ $labelOption['label'] }}</span>
                        @if ($isSelected)
                            <x-icon icon="check" class="size-4 shrink-0 text-ink-muted" />
                        @endif
                    </button>
                </li>
            @endforeach
        </ul>

        @if ($this->labelOptions === [])
            <p x-show="term === ''" class="px-3 pb-2 text-sm text-ink-muted">Aún no hay etiquetas. Escribe un nombre para crear la primera.</p>
        @endif

        <div x-show="term !== '' && ! exists" class="border-t border-line p-1">
            <button type="button" x-on:click="submit()"
                    class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm text-ink hover:bg-hover">
                <x-icon icon="plus" class="size-4 shrink-0 text-ink-muted" />
                <span class="min-w-0 truncate">Crear «<span x-text="search.trim()"></span>»</span>
            </button>
        </div>
    </div>

    <p class="mt-1 text-xs text-ink-subtle">Etiquetas de wiki del espacio, distintas de las de tareas.</p>
    <x-error property="newLabel" />
</div>
