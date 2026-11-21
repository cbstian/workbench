<x-ui.page>
    <x-ui.page-header :title="$project->name"
                      :icon="$project->icon"
                      :description="$project->description"
                      :breadcrumbs="[
                          ['label' => 'Proyectos', 'link' => route('projects.index')],
                          ['label' => $project->name],
                      ]">
        <x-slot:actions>
            @if ($project->client)
                <span class="flex items-center gap-2 text-sm text-ink-muted">
                    <x-app.client-logo :client="$project->client" size="sm" />
                    {{ $project->client->name }}
                </span>
            @endif

            @if ($project->is_visible_to_client)
                <x-badge text="Visible para el cliente" color="primary" light round />
            @else
                <x-badge text="Oculto para el cliente" color="secondary" light round />
            @endif

            @can('configure', $project)
                <x-button icon="cog-6-tooth" text="Configurar" sm outline :href="route('projects.settings', $project)" wire:navigate />
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-ui.section title="Tareas" class="lg:col-span-2">
            <x-ui.empty-state icon="check-circle" title="Aún no hay tareas" description="Las tareas, sus vistas (kanban, listado y calendario) y la wiki del proyecto se mostrarán aquí." />
        </x-ui.section>

        <div class="space-y-6">
            <x-ui.section title="Equipo">
                @if ($project->members->isEmpty())
                    <p class="text-sm text-ink-muted">Sin integrantes asignados.</p>
                @else
                    <ul class="space-y-2">
                        @foreach ($project->members as $member)
                            <li wire:key="team-{{ $member->id }}" class="flex items-center gap-2 text-sm text-ink">
                                <x-app.avatar :name="$member->name" />
                                <span class="truncate">{{ $member->name }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.section>

            <x-ui.section title="Estados">
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($project->statuses as $status)
                        <x-app.color-tag :color="$status->color" :text="$status->name" :initial="$status->is_initial" wire:key="status-{{ $status->id }}" />
                    @endforeach
                </div>
            </x-ui.section>

            <x-ui.section title="Prioridades">
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($project->priorities as $priority)
                        <x-app.color-tag :color="$priority->color" :text="$priority->name" :initial="$priority->is_initial" wire:key="priority-{{ $priority->id }}" />
                    @endforeach
                </div>
            </x-ui.section>
        </div>
    </div>
</x-ui.page>
