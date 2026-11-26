{{--
    Comment textarea with @mention autocomplete (Alpine `wbMentions`).

    Variables: $model (wire:model target), $submit (method), $submitText,
    $placeholder, $cancel (optional method), $autofocus (bool).
--}}
<form wire:submit="{{ $submit }}" class="space-y-2">
    <div class="relative"
         x-data="wbMentions(@js($this->mentionables))"
         x-on:input="update($event)"
         x-on:click="update($event)"
         x-on:keydown="keydown($event)"
         x-on:keydown.ctrl.enter.prevent="$wire.{{ $submit }}()"
         x-on:keydown.meta.enter.prevent="$wire.{{ $submit }}()"
         x-on:click.outside="close()"
         @if ($autofocus ?? false) x-init="$nextTick(() => $el.querySelector('textarea')?.focus())" @endif>
        <x-textarea wire:model="{{ $model }}" rows="3" :placeholder="$placeholder" />

        <ul x-cloak x-show="open" x-transition.opacity.duration.100ms role="listbox" aria-label="Personas para mencionar"
            :class="above ? 'bottom-full mb-1' : 'top-full mt-1'"
            class="absolute inset-x-0 z-20 max-h-56 overflow-y-auto rounded-lg border border-line bg-elevated p-1 shadow-lg">
            <template x-for="(user, index) in matches" :key="user.id">
                <li role="option" :aria-selected="index === active"
                    x-on:mousedown.prevent="choose(user)"
                    x-on:mouseenter="active = index"
                    :class="index === active ? 'bg-hover' : ''"
                    class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm text-ink">
                    <span class="inline-flex size-6 items-center justify-center rounded-md bg-hover text-[11px] font-semibold text-ink-muted uppercase" x-text="initials(user.name)"></span>
                    <span x-text="user.name"></span>
                </li>
            </template>
        </ul>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-xs text-ink-subtle">Escribe @ para mencionar · Ctrl + Enter para enviar</p>
        <div class="flex items-center gap-2">
            @isset($cancel)
                <x-button flat color="secondary" text="Cancelar" sm wire:click="{{ $cancel }}" />
            @endisset
            <x-button type="submit" :text="$submitText" sm loading="{{ $submit }}" />
        </div>
    </div>
</form>
