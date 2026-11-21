<x-ui.page>
    <x-ui.page-header title="Clientes"
                      :description="'Clientes de '.$this->workspace->name.'. Cada uno puede tener cero o muchos proyectos y sus usuarios cliente.'">
        <x-slot:actions>
            <x-button icon="plus" text="Nuevo cliente" sm wire:click="create" />
        </x-slot:actions>
    </x-ui.page-header>

    <div class="max-w-sm">
        <x-input icon="magnifying-glass" placeholder="Buscar clientes…" wire:model.live.debounce.300ms="search" />
    </div>

    @if ($this->clients->isEmpty())
        @if ($search !== '')
            <x-ui.empty-state icon="magnifying-glass" title="No encontramos clientes con ese criterio." />
        @else
            <x-ui.empty-state icon="building-office" title="Aún no hay clientes" description="Crea un cliente para relacionarle proyectos y usuarios cliente.">
                <x-button icon="plus" text="Nuevo cliente" sm wire:click="create" />
            </x-ui.empty-state>
        @endif
    @else
        <ul class="divide-y divide-line rounded-lg border border-line bg-surface">
            @foreach ($this->clients as $client)
                <li wire:key="client-{{ $client->id }}" class="flex items-center gap-4 px-4 py-3">
                    <x-app.client-logo :client="$client" />

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-ink">{{ $client->name }}</p>
                        <p class="truncate text-xs text-ink-muted">{{ $client->description ?? 'Sin descripción' }}</p>
                    </div>

                    <div class="hidden shrink-0 items-center gap-2 sm:flex">
                        <x-badge :text="trans_choice('{0} Sin proyectos|{1} 1 proyecto|[2,*] :count proyectos', $client->projects_count)" color="secondary" light round />
                        <x-badge :text="trans_choice('{0} Sin usuarios|{1} 1 usuario|[2,*] :count usuarios', $client->users_count)" color="secondary" light round />
                    </div>

                    <x-dropdown icon="ellipsis-horizontal" static>
                        <x-dropdown.items icon="folder" text="Ver proyectos" :href="route('projects.index', ['client' => $client->uuid])" navigate />
                        <x-dropdown.items icon="pencil-square" text="Editar" wire:click="edit('{{ $client->uuid }}')" />
                        <x-dropdown.items icon="trash" text="Enviar a la papelera" separator wire:click="confirmDelete('{{ $client->uuid }}')" />
                    </x-dropdown>
                </li>
            @endforeach
        </ul>
    @endif

    <x-slide wire="showForm" :title="$form->client ? 'Editar cliente' : 'Nuevo cliente'" size="lg">
        <form id="client-form" wire:submit="save" class="space-y-5">
            <x-input label="Nombre *" wire:model="form.name" />
            <x-textarea label="Descripción" wire:model="form.description" rows="3" />
            <x-color label="Color" wire:model="form.color" picker clearable hint="Identifica al cliente en listados y proyectos." />

            <div class="space-y-2">
                @if ($form->client?->logo_path && ! $form->removeLogo && ! $form->logo)
                    <div class="flex items-center gap-3">
                        <x-app.client-logo :client="$form->client" size="lg" />
                        <x-button text="Quitar logo" sm flat color="red" wire:click="$set('form.removeLogo', true)" />
                    </div>
                @endif

                <x-upload label="Logo" wire:model="form.logo" accept="image/*" tip="PNG, JPG o WEBP de hasta 2 MB" delete />
            </div>
        </form>

        <x-slot:footer>
            <x-button flat color="secondary" text="Cancelar" sm wire:click="$set('showForm', false)" />
            <x-button type="submit" form="client-form" text="Guardar" sm loading="save" />
        </x-slot:footer>
    </x-slide>
</x-ui.page>
