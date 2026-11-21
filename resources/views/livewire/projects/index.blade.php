<x-ui.page>
    <x-ui.page-header title="Proyectos"
                      :description="$this->selectedClient ? 'Proyectos de '.$this->selectedClient->name.'.' : 'Proyectos de '.$this->workspace->name.'.'">
        @if ($this->canCreate)
            <x-slot:actions>
                <x-button icon="plus" text="Nuevo proyecto" sm wire:click="create" />
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    @if ($this->clientFilterOptions !== [])
        <div class="max-w-xs">
            <x-select.styled wire:model.live="client" :options="$this->clientFilterOptions" placeholder="Todos los clientes" searchable />
        </div>
    @endif

    <x-table :headers="$this->headers"
             :rows="$this->rows"
             :sort="$sort"
             :quantity="\App\Livewire\Projects\Index::PAGE_SIZES"
             :placeholders="['search' => 'Buscar proyectos…', 'empty' => 'No encontramos proyectos con ese criterio.']"
             filter
             paginate
             loading
             striped>
        @interact('column_name', $row)
            <a href="{{ route('projects.show', $row) }}" wire:navigate class="flex items-center gap-2.5 hover:underline">
                <span class="text-base leading-none" aria-hidden="true">{{ $row->icon }}</span>
                <span class="font-medium text-ink">{{ $row->name }}</span>
            </a>
        @endinteract

        @interact('column_client', $row)
            @if ($row->client)
                <span class="flex items-center gap-2">
                    <x-app.client-logo :client="$row->client" size="sm" />
                    <span class="truncate text-ink">{{ $row->client->name }}</span>
                </span>
            @else
                <span class="text-ink-subtle">—</span>
            @endif
        @endinteract

        @interact('column_description', $row)
            <span class="block max-w-md truncate text-ink-muted">{{ $row->description ?? '—' }}</span>
        @endinteract

        @interact('column_is_visible_to_client', $row)
            @if ($row->is_visible_to_client)
                <x-badge text="Visible" color="primary" light round />
            @else
                <x-badge text="Oculto" color="secondary" light round />
            @endif
        @endinteract

        @interact('column_updated_at', $row)
            <time datetime="{{ $row->updated_at->toIso8601String() }}" class="whitespace-nowrap text-ink-muted">
                {{ $row->updated_at->translatedFormat('j M Y') }}
            </time>
        @endinteract

        @interact('column_action', $row)
            <x-dropdown icon="ellipsis-horizontal" static>
                <x-dropdown.items icon="arrow-top-right-on-square" text="Abrir" :href="route('projects.show', $row)" navigate />
                @can('configure', $row)
                    <x-dropdown.items icon="cog-6-tooth" text="Configurar" :href="route('projects.settings', $row)" navigate />
                @endcan
                @can('delete', $row)
                    <x-dropdown.items icon="trash" text="Enviar a la papelera" separator wire:click="confirmDelete('{{ $row->uuid }}')" />
                @endcan
            </x-dropdown>
        @endinteract
    </x-table>

    @if ($this->canCreate)
        <x-slide wire="showForm" title="Nuevo proyecto" size="xl">
            <form id="project-form" wire:submit="save" class="space-y-6">
                @include('livewire.projects.partials.fields', ['clientOptions' => $this->clientOptions])

                <x-select.styled label="Equipo" wire:model="form.team_ids" :options="$this->teamOptions"
                                 select="label:label|value:value|description:description"
                                 multiple searchable
                                 hint="Admins y miembros del espacio. Puedes cambiarlo después en la configuración." />
            </form>

            <x-slot:footer>
                <x-button flat color="secondary" text="Cancelar" sm wire:click="$set('showForm', false)" />
                <x-button type="submit" form="project-form" text="Crear proyecto" sm loading="save" />
            </x-slot:footer>
        </x-slide>
    @endif
</x-ui.page>
