<x-ui.page>
    <x-ui.page-header title="Usuarios"
                      :description="'Admins, miembros y usuarios cliente de '.$this->workspace->name.'.'">
        <x-slot:actions>
            <x-button icon="user-plus" text="Agregar existente" sm outline wire:click="addExisting" />
            <x-button icon="plus" text="Nuevo usuario" sm wire:click="create" />
        </x-slot:actions>
    </x-ui.page-header>

    <div class="flex flex-wrap items-center gap-3">
        <div class="w-full max-w-sm">
            <x-input icon="magnifying-glass" placeholder="Buscar por nombre o correo…" wire:model.live.debounce.300ms="search" />
        </div>

        <div class="flex flex-wrap gap-1" role="group" aria-label="Filtrar por rol">
            @foreach (['' => 'Todos'] + collect(\App\Enums\WorkspaceRole::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all() as $value => $label)
                <button type="button"
                        wire:click="$set('role', '{{ $value }}')"
                        @class([
                            'inline-flex h-8 items-center gap-1.5 rounded-md px-3 text-sm transition-colors',
                            'bg-active font-medium text-ink' => $role === $value,
                            'text-ink-muted hover:bg-hover hover:text-ink' => $role !== $value,
                        ])>
                    {{ $label }}
                    <span class="text-xs text-ink-subtle tabular-nums">{{ $value === '' ? array_sum($this->roleCounts) : ($this->roleCounts[$value] ?? 0) }}</span>
                </button>
            @endforeach
        </div>
    </div>

    @if ($this->members->isEmpty())
        @if ($search !== '' || $role !== '')
            <x-ui.empty-state icon="magnifying-glass" title="No encontramos usuarios con ese criterio." />
        @else
            <x-ui.empty-state icon="users" title="Aún no hay usuarios en este espacio" description="Crea admins, miembros o usuarios cliente, o agrega usuarios de tu cuenta.">
                <x-button icon="plus" text="Nuevo usuario" sm wire:click="create" />
            </x-ui.empty-state>
        @endif
    @else
        <div class="overflow-x-auto rounded-lg border border-line bg-surface">
            <table class="w-full min-w-[640px] text-sm">
                <thead>
                    <tr class="border-b border-line text-left text-xs text-ink-muted">
                        <th class="px-4 py-2.5 font-medium">Usuario</th>
                        <th class="px-4 py-2.5 font-medium">Rol</th>
                        <th class="px-4 py-2.5 font-medium">Acceso</th>
                        <th class="px-4 py-2.5 font-medium">Estado</th>
                        <th class="px-4 py-2.5"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($this->members as $member)
                        @php($memberRole = $member->pivot->role)
                        <tr wire:key="member-{{ $member->id }}">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <x-app.avatar :name="$member->name" />
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-ink">{{ $member->name }}</p>
                                        <p class="truncate text-xs text-ink-muted">{{ $member->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <x-badge :text="$memberRole->label()" :color="$memberRole->color()" light round />
                            </td>
                            <td class="px-4 py-3 text-ink-muted">
                                @if ($memberRole === \App\Enums\WorkspaceRole::Client)
                                    {{ $member->clients->pluck('name')->join(', ') ?: 'Sin clientes' }}
                                @else
                                    {{ trans_choice('{0} Sin proyectos|{1} 1 proyecto|[2,*] :count proyectos', $member->projects_count) }}
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($member->deactivated_at)
                                    <x-badge text="Desactivado" color="red" light round />
                                @else
                                    <x-badge text="Activo" color="green" light round />
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <x-dropdown icon="ellipsis-horizontal" static>
                                    <x-dropdown.items icon="pencil-square" text="Editar" wire:click="edit('{{ $member->uuid }}')" />
                                    @if ($member->deactivated_at)
                                        <x-dropdown.items icon="lock-open" text="Reactivar acceso" wire:click="toggleActive('{{ $member->uuid }}')" />
                                    @else
                                        <x-dropdown.items icon="lock-closed" text="Desactivar acceso" wire:click="toggleActive('{{ $member->uuid }}')" />
                                    @endif
                                    <x-dropdown.items icon="user-minus" text="Quitar del espacio" separator wire:click="confirmRemove('{{ $member->uuid }}')" />
                                    <x-dropdown.items icon="trash" text="Eliminar usuario" wire:click="confirmDelete('{{ $member->uuid }}')" />
                                </x-dropdown>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($this->deletedUsers->isNotEmpty())
        <x-ui.section title="Usuarios eliminados" description="Usuarios de tu cuenta eliminados de forma lógica. No pueden entrar; al restaurarlos recuperan su acceso.">
            <ul class="divide-y divide-line rounded-lg border border-line">
                @foreach ($this->deletedUsers as $deletedUser)
                    <li wire:key="deleted-user-{{ $deletedUser->id }}" class="flex items-center gap-3 px-4 py-3">
                        <x-app.avatar :name="$deletedUser->name" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-ink">{{ $deletedUser->name }}</p>
                            <p class="truncate text-xs text-ink-muted">{{ $deletedUser->email }} · eliminado {{ $deletedUser->deleted_at->diffForHumans() }}</p>
                        </div>
                        <x-button text="Restaurar" icon="arrow-uturn-left" sm flat wire:click="restore('{{ $deletedUser->uuid }}')" />
                    </li>
                @endforeach
            </ul>
        </x-ui.section>
    @endif

    <x-slide wire="showForm" size="lg"
             :title="match ($form->mode) { 'existing' => 'Agregar usuario existente', 'edit' => 'Editar usuario', default => 'Nuevo usuario' }">
        <form id="user-form" wire:submit="save" class="space-y-5">
            @if ($form->mode === 'existing')
                @if ($this->addableUserOptions === [])
                    <x-ui.empty-state icon="users" title="No hay usuarios disponibles" description="Todos los usuarios de tu cuenta ya están en este espacio." />
                @else
                    <x-select.styled label="Usuario *" wire:model="form.existing_user_id" :options="$this->addableUserOptions"
                                     select="label:label|value:value|description:description" searchable />
                @endif
            @else
                <x-input label="Nombre *" wire:model="form.name" autocomplete="off" />
                <x-input label="Correo *" type="email" wire:model="form.email" autocomplete="off" />
                <x-password :label="$form->mode === 'create' ? 'Contraseña *' : 'Nueva contraseña'"
                            wire:model="form.password"
                            autocomplete="new-password"
                            :hint="$form->mode === 'create' ? 'Mínimo 8 caracteres. Compártela con el usuario por un canal seguro.' : 'Déjala vacía para mantener la actual.'" />
            @endif

            <x-select.styled label="Rol *" wire:model.live="form.role" :options="\App\Enums\WorkspaceRole::options()"
                             select="label:label|value:value|description:description" required />

            @if ($form->isClientRole())
                <x-select.styled label="Clientes *" wire:model="form.client_ids" :options="$this->clientOptions"
                                 multiple searchable
                                 hint="Un usuario cliente puede pertenecer a más de un cliente. Por ahora entra sin ver nada." />
            @else
                <x-select.styled label="Proyectos" wire:model="form.project_ids" :options="$this->projectOptions"
                                 multiple searchable
                                 hint="Admins y miembros ven solo los proyectos a los que están asignados." />
            @endif
        </form>

        <x-slot:footer>
            <x-button flat color="secondary" text="Cancelar" sm wire:click="$set('showForm', false)" />
            <x-button type="submit" form="user-form" text="Guardar" sm loading="save" />
        </x-slot:footer>
    </x-slide>
</x-ui.page>
