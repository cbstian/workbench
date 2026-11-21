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
        <x-ui.section title="Tareas" class="min-w-0 lg:col-span-2">
            @if ($this->canCreateTasks)
                <x-slot:actions>
                    <x-button icon="plus" text="Nueva tarea" sm wire:click="createTask" />
                </x-slot:actions>
            @endif

            @if ($this->tasks->isEmpty())
                <x-ui.empty-state icon="check-circle" title="Aún no hay tareas" description="Crea la primera; toma el estado y la prioridad iniciales del proyecto.">
                    @if ($this->canCreateTasks)
                        <x-button icon="plus" text="Nueva tarea" sm wire:click="createTask" />
                    @endif
                </x-ui.empty-state>
            @else
                <div class="space-y-5">
                    @foreach ($project->statuses as $status)
                        @php($statusTasks = $this->tasks->where('status_id', $status->id))
                        @continue($statusTasks->isEmpty())

                        <div wire:key="status-group-{{ $status->id }}" class="space-y-1.5">
                            <div class="flex items-center gap-2 px-1 text-xs font-medium text-ink-muted">
                                <span class="size-2 rounded-full bg-ink-subtle" @if ($status->color) style="background-color: {{ $status->color }}" @endif aria-hidden="true"></span>
                                {{ $status->name }}
                                <span class="text-ink-subtle tabular-nums">{{ $statusTasks->count() }}</span>
                            </div>

                            <ul class="divide-y divide-line rounded-lg border border-line bg-surface">
                                @foreach ($statusTasks as $task)
                                    <li wire:key="task-{{ $task->id }}">
                                        <a href="{{ route('tasks.show', $task) }}" wire:navigate
                                           class="flex items-center gap-3 px-3 py-2.5 transition-colors hover:bg-hover">
                                            <span @class(['min-w-0 flex-1 truncate text-sm', 'text-ink-muted line-through' => $task->completed_at, 'text-ink' => ! $task->completed_at])>{{ $task->name }}</span>

                                            @if ($task->subtasks_count > 0)
                                                <span class="flex shrink-0 items-center gap-1 text-xs text-ink-subtle tabular-nums" title="Subtareas cerradas">
                                                    <x-icon icon="list-bullet" class="size-3.5" />
                                                    {{ $task->closed_subtasks_count }}/{{ $task->subtasks_count }}
                                                </span>
                                            @endif

                                            @foreach ($task->labels as $label)
                                                <x-app.color-tag :color="$label->color" :text="$label->name" class="hidden shrink-0 md:inline-flex" />
                                            @endforeach

                                            <x-app.color-tag :color="$task->priority->color" :text="$task->priority->name" class="hidden shrink-0 sm:inline-flex" />

                                            @if ($task->ends_on)
                                                @php($isOverdue = ! $task->completed_at && $task->ends_on->lt(today()))
                                                <time datetime="{{ $task->ends_on->toDateString() }}"
                                                      @if ($isOverdue) title="Vencida" @endif
                                                      @class(['flex shrink-0 items-center gap-1 text-xs tabular-nums', 'font-semibold text-ink' => $isOverdue, 'text-ink-subtle' => ! $isOverdue])>
                                                    @if ($isOverdue)
                                                        <x-icon icon="exclamation-circle" class="size-3.5" />
                                                    @endif
                                                    {{ $task->ends_on->translatedFormat('j M') }}
                                                </time>
                                            @endif

                                            <span class="flex shrink-0 -space-x-1.5">
                                                @foreach ($task->assignees->take(3) as $assignee)
                                                    <x-app.avatar :name="$assignee->name" class="size-6 ring-2 ring-surface" />
                                                @endforeach
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endif
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

    @if ($this->canCreateTasks)
        <x-slide id="task-slide" wire="showTaskForm" title="Nueva tarea" size="xl">
            <form id="task-form" wire:submit="saveTask">
                @include('livewire.tasks.partials.fields')
            </form>

            <x-slot:footer>
                <x-button flat color="secondary" text="Cancelar" sm wire:click="$set('showTaskForm', false)" />
                <x-button type="submit" form="task-form" text="Crear tarea" sm loading="saveTask" />
            </x-slot:footer>
        </x-slide>
    @endif
</x-ui.page>
