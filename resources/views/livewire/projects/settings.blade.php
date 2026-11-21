<x-ui.page width="wide">
    <x-ui.page-header title="Configuración"
                      :icon="$project->icon"
                      :description="'Datos, equipo y flujo de trabajo de '.$project->name.'. Los cambios aplican solo a este proyecto.'"
                      :breadcrumbs="[
                          ['label' => 'Proyectos', 'link' => route('projects.index')],
                          ['label' => $project->name, 'link' => route('projects.show', $project)],
                          ['label' => 'Configuración'],
                      ]" />

    <div>
        @can('update', $project)
            <form wire:submit="saveGeneral">
                <x-ui.form-section title="General" description="Nombre, icono, cliente y visibilidad para el cliente.">
                    @include('livewire.projects.partials.fields', ['clientOptions' => $this->clientOptions])

                    <x-slot:actions>
                        <x-ui.form-actions>
                            <x-button type="submit" text="Guardar cambios" sm loading="saveGeneral" />
                        </x-ui.form-actions>
                    </x-slot:actions>
                </x-ui.form-section>
            </form>
        @endcan

        <x-ui.form-section title="Equipo" description="Admins y miembros que ven este proyecto. Solo el Super admin agrega usuarios existentes.">
            <div class="space-y-4">
                @if ($this->team->isEmpty())
                    <p class="text-sm text-ink-muted">Aún no hay integrantes.</p>
                @else
                    <ul class="divide-y divide-line rounded-lg border border-line bg-surface">
                        @foreach ($this->team as $member)
                            @php($memberRole = $member->workspaces->first()?->pivot->role)
                            <li wire:key="member-{{ $member->id }}" class="flex items-center gap-3 px-3 py-2">
                                <x-app.avatar :name="$member->name" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm text-ink">{{ $member->name }}</p>
                                    <p class="truncate text-xs text-ink-muted">{{ $member->email }}</p>
                                </div>
                                @if ($memberRole)
                                    <x-badge :text="$memberRole->label()" :color="$memberRole->color()" light round />
                                @endif
                                @can('manageTeam', $project)
                                    <x-button.circle icon="x-mark" flat color="secondary" sm
                                                     wire:click="confirmRemoveMember({{ $member->id }})"
                                                     :aria-label="'Quitar a '.$member->name" />
                                @endcan
                            </li>
                        @endforeach
                    </ul>
                @endif

                @can('manageTeam', $project)
                    @if ($this->candidateOptions === [])
                        <p class="text-xs text-ink-subtle">
                            Todos los admins y miembros del espacio ya están en el equipo.
                            @can('manage-workspace') <a href="{{ route('users.index') }}" wire:navigate class="underline">Crear usuarios</a>@endcan
                        </p>
                    @else
                        <form wire:submit="addMembers" class="flex flex-col gap-2 sm:flex-row sm:items-start">
                            <div class="min-w-0 flex-1">
                                <x-select.styled wire:model="newMemberIds" :options="$this->candidateOptions"
                                                 select="label:label|value:value|description:description"
                                                 multiple searchable placeholder="Agregar integrantes…" />
                            </div>
                            <x-button type="submit" text="Agregar" sm loading="addMembers" />
                        </form>
                    @endif
                @endcan
            </div>
        </x-ui.form-section>

        <livewire:projects.workflow-values :project="$project" type="statuses" wire:key="workflow-statuses" />
        <livewire:projects.workflow-values :project="$project" type="priorities" wire:key="workflow-priorities" />
        <livewire:projects.task-labels :project="$project" wire:key="task-labels" />

        @can('delete', $project)
            <x-ui.form-section title="Zona de peligro" description="El proyecto va a la papelera con sus tareas y su wiki; puedes restaurarlo desde ahí.">
                <div>
                    <x-button icon="trash" text="Enviar a la papelera" color="red" sm outline wire:click="confirmDelete" />
                </div>
            </x-ui.form-section>
        @endcan
    </div>
</x-ui.page>
