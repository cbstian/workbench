<div @class(['px-2 pb-1' => $this->canSwitch])>
    @if ($this->canSwitch)
        <x-dropdown position="bottom-start" width="lg">
            <x-slot:action>
                <button type="button"
                        x-on:click="show = !show"
                        class="flex h-9 w-full items-center gap-2 rounded-md border border-line bg-surface px-2 text-left text-sm transition-colors hover:bg-hover"
                        aria-label="Cambiar espacio de trabajo">
                    <span class="flex size-5 shrink-0 items-center justify-center rounded bg-active text-[11px] font-semibold text-ink" aria-hidden="true">
                        {{ str($this->current?->name ?? '?')->substr(0, 1)->upper() }}
                    </span>
                    <span class="min-w-0 flex-1 truncate font-medium text-ink">{{ $this->current?->name ?? 'Sin espacio de trabajo' }}</span>
                    <x-icon icon="chevron-up-down" class="size-4 shrink-0 text-ink-subtle" />
                </button>
            </x-slot:action>

            <div class="px-3 pt-2 pb-1 text-xs font-medium text-ink-subtle">Espacios de trabajo</div>

            @forelse ($this->workspaces as $workspace)
                <x-dropdown.items wire:key="switch-{{ $workspace->id }}" wire:click="switchTo('{{ $workspace->uuid }}')">
                    <div class="flex w-full items-center gap-2">
                        <span class="min-w-0 flex-1 truncate">{{ $workspace->name }}</span>
                        @if ($this->current?->is($workspace))
                            <x-icon icon="check" class="size-4 text-ink" />
                        @endif
                    </div>
                </x-dropdown.items>
            @empty
                <p class="px-3 py-2 text-sm text-ink-muted">Aún no tienes espacios.</p>
            @endforelse

            @can('manage-account')
                <x-dropdown.items icon="cog-6-tooth" text="Gestionar espacios" :href="route('workspaces.index')" navigate separator />
            @endcan
        </x-dropdown>
    @endif
</div>
