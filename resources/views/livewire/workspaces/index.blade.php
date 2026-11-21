<x-ui.page>
    <x-ui.page-header title="Espacios de trabajo"
                      description="Los espacios de tu cuenta. Trabajas en uno a la vez; cámbialo desde la barra lateral.">
        <x-slot:actions>
            <x-button icon="plus" text="Nuevo espacio" sm wire:click="create" />
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.section title="Activos">
        @if ($this->workspaces->isEmpty())
            <x-ui.empty-state icon="squares-2x2" title="Aún no tienes espacios de trabajo" description="Crea el primero para agregar clientes, proyectos y usuarios.">
                <x-button icon="plus" text="Nuevo espacio" sm wire:click="create" />
            </x-ui.empty-state>
        @else
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($this->workspaces as $workspace)
                    <div wire:key="workspace-{{ $workspace->id }}" class="flex flex-col gap-4 rounded-lg border border-line bg-surface p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-ink">{{ $workspace->name }}</p>
                                <p class="text-xs text-ink-subtle">Creado {{ $workspace->created_at->translatedFormat('j M Y') }}</p>
                            </div>

                            @if ($this->currentWorkspaceId === $workspace->id)
                                <x-badge text="Actual" color="primary" light round />
                            @endif
                        </div>

                        <dl class="grid grid-cols-3 gap-2 text-center">
                            @foreach (['projects_count' => 'Proyectos', 'clients_count' => 'Clientes', 'members_count' => 'Usuarios'] as $count => $label)
                                <div class="rounded-md bg-hover px-2 py-2">
                                    <dt class="text-xs text-ink-muted">{{ $label }}</dt>
                                    <dd class="text-lg font-semibold text-ink tabular-nums">{{ $workspace->{$count} }}</dd>
                                </div>
                            @endforeach
                        </dl>

                        <div class="mt-auto flex items-center justify-between gap-2">
                            <x-button text="Abrir" sm outline wire:click="open('{{ $workspace->uuid }}')" />

                            <x-dropdown icon="ellipsis-horizontal" static>
                                <x-dropdown.items icon="pencil-square" text="Renombrar" wire:click="edit('{{ $workspace->uuid }}')" />
                                <x-dropdown.items icon="trash" text="Enviar a la papelera" separator wire:click="confirmDelete('{{ $workspace->uuid }}')" />
                            </x-dropdown>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.section>

    @if ($this->trashedWorkspaces->isNotEmpty())
        <x-ui.section title="En la papelera" description="Al restaurar un espacio, su contenido vuelve tal como estaba.">
            <ul class="divide-y divide-line rounded-lg border border-line">
                @foreach ($this->trashedWorkspaces as $workspace)
                    <li wire:key="trashed-workspace-{{ $workspace->id }}" class="flex items-center gap-3 px-4 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-ink">{{ $workspace->name }}</p>
                            <p class="text-xs text-ink-muted">
                                Eliminado {{ $workspace->deleted_at->diffForHumans() }}@if ($workspace->deleter) por {{ $workspace->deleter->name }}@endif
                            </p>
                        </div>
                        <x-button text="Restaurar" icon="arrow-uturn-left" sm flat wire:click="restore('{{ $workspace->uuid }}')" />
                    </li>
                @endforeach
            </ul>
        </x-ui.section>
    @endif

    <x-slide wire="showForm" :title="$form->workspace ? 'Renombrar espacio' : 'Nuevo espacio de trabajo'" size="md">
        <form id="workspace-form" wire:submit="save" class="space-y-4">
            <x-input label="Nombre *" wire:model="form.name" hint="Por ejemplo: Estudio, Clientes, Personal." />
        </form>

        <x-slot:footer>
            <x-button flat color="secondary" text="Cancelar" sm wire:click="$set('showForm', false)" />
            <x-button type="submit" form="workspace-form" text="Guardar" sm loading="save" />
        </x-slot:footer>
    </x-slide>
</x-ui.page>
