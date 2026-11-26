<div class="space-y-4" x-data="{ showFilters: @js($this->filters->count() > 0) }">
    {{-- Toolbar: view switcher, search, filters and new task --}}
    <div class="flex flex-wrap items-center gap-2">
        <div class="inline-flex items-center gap-1 rounded-lg bg-hover p-1" role="tablist" aria-label="Vista de tareas">
            @foreach (\App\Enums\TaskView::cases() as $taskView)
                <button type="button" role="tab"
                        wire:click="$set('view', '{{ $taskView->value }}')"
                        aria-selected="{{ $view === $taskView->value ? 'true' : 'false' }}"
                        @class([
                            'inline-flex h-7 items-center gap-1.5 rounded-md px-2.5 text-sm transition-colors',
                            'bg-surface font-medium text-ink shadow-xs' => $view === $taskView->value,
                            'text-ink-muted hover:text-ink' => $view !== $taskView->value,
                        ])>
                    <x-icon :icon="$taskView->icon()" class="size-4" />
                    {{ $taskView->label() }}
                </button>
            @endforeach
        </div>

        <div class="w-full max-w-xs sm:w-56">
            <x-input icon="magnifying-glass" placeholder="Buscar tareas…" wire:model.live.debounce.400ms="search" />
        </div>

        <x-button icon="funnel" sm flat color="secondary" x-on:click="showFilters = ! showFilters"
                  :text="'Filtros'.($this->filters->count() > 0 ? ' ('.$this->filters->count().')' : '')" />

        @if (auth()->user()->isOnTeamOf($project))
            <x-button icon="user" text="Mis tareas" sm flat color="secondary" wire:click="onlyMine" />
        @endif

        @unless ($this->filters->isEmpty())
            <x-button icon="x-mark" text="Limpiar" sm flat color="secondary" wire:click="clearFilters" />
        @endunless

        @if ($this->canCreateTasks)
            <div class="ml-auto">
                <x-button icon="plus" text="Nueva tarea" sm wire:click="createTask" />
            </div>
        @endif
    </div>

    {{-- Filters (remembered per user) --}}
    <div x-show="showFilters" x-collapse x-cloak>
        <div class="grid gap-3 rounded-lg border border-line bg-surface p-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <x-select.styled label="Estado" wire:model.live="statusIds" :options="$this->statusOptions" multiple placeholder="Todos" />
            <x-select.styled label="Prioridad" wire:model.live="priorityIds" :options="$this->priorityOptions" multiple placeholder="Todas" />
            <x-select.styled label="Asignado" wire:model.live="assigneeIds" :options="$this->assigneeOptions" multiple searchable placeholder="Todos" />
            <x-select.styled label="Etiqueta" wire:model.live="labelIds" :options="$this->labelOptions" multiple placeholder="Todas" />
            <x-input label="Desde" type="date" wire:model.live="dateFrom" />
            <x-input label="Hasta" type="date" wire:model.live="dateTo" />
        </div>
    </div>

    @if ($view === 'kanban')
        {{-- Kanban: one column per status, drag to move --}}
        <div class="scrollbar-subtle -mx-1 flex gap-3 overflow-x-auto px-1 pb-2">
            @foreach ($this->columns as $column)
                @php($columnTasks = $this->tasks->where('status_id', $column->id))
                <section wire:key="column-{{ $column->id }}" class="flex w-72 shrink-0 flex-col rounded-lg bg-surface-muted p-2" aria-label="{{ $column->name }}">
                    <header class="flex items-center gap-2 px-1.5 pb-2">
                        <span class="size-2 rounded-full bg-ink-subtle" @if ($column->color) style="background-color: {{ $column->color }}" @endif aria-hidden="true"></span>
                        <h3 class="min-w-0 flex-1 truncate text-sm font-medium text-ink">{{ $column->name }}</h3>
                        <span class="text-xs text-ink-subtle tabular-nums">{{ $columnTasks->count() }}</span>
                        @if ($this->canCreateTasks)
                            <button type="button" wire:click="createTask({{ $column->id }})"
                                    class="inline-flex size-6 items-center justify-center rounded text-ink-subtle hover:bg-hover hover:text-ink"
                                    aria-label="Nueva tarea en {{ $column->name }}">
                                <x-icon icon="plus" class="size-4" />
                            </button>
                        @endif
                    </header>

                    <ul wire:sort="moveTask" wire:sort:group="tasks" wire:sort:group-id="{{ $column->id }}"
                        class="flex min-h-16 flex-1 flex-col gap-2">
                        @foreach ($columnTasks as $task)
                            <li wire:key="card-{{ $task->id }}" wire:sort:item="{{ $task->id }}" wire:click="editTask({{ $task->id }})"
                                class="cursor-pointer rounded-md border border-line bg-surface p-2.5 shadow-xs transition-colors hover:border-line-strong active:cursor-grabbing">
                                <button type="button"
                                        @class(['block w-full text-left text-sm', 'text-ink-muted line-through' => $task->completed_at, 'text-ink' => ! $task->completed_at])>
                                    {{ $task->name }}
                                </button>

                                @if ($task->labels->isNotEmpty())
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        @foreach ($task->labels as $label)
                                            <x-app.color-tag :color="$label->color" :text="$label->name" />
                                        @endforeach
                                    </div>
                                @endif

                                <div class="mt-2 flex items-center gap-2 text-xs text-ink-subtle">
                                    <x-app.color-tag :color="$task->priority->color" :text="$task->priority->name" />
                                    @include('livewire.projects.partials.task-meta', ['task' => $task])
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
    @elseif ($view === 'list')
        {{-- List: sortable table --}}
        @if ($this->listTasks->isEmpty())
            <x-ui.empty-state icon="check-circle"
                              :title="$this->filters->isEmpty() ? 'Aún no hay tareas' : 'Ninguna tarea cumple los filtros'"
                              :description="$this->filters->isEmpty() ? 'Crea la primera; toma el estado y la prioridad iniciales del proyecto.' : null" />
        @else
            <div class="overflow-x-auto rounded-lg border border-line bg-surface">
                <table class="w-full min-w-[760px] text-sm">
                    <thead>
                        <tr class="border-b border-line text-left text-xs text-ink-muted">
                            @foreach (['name' => 'Tarea', 'status' => 'Estado', 'priority' => 'Prioridad', 'ends_on' => 'Fechas'] as $column => $label)
                                <th class="px-4 py-2.5 font-medium">
                                    <button type="button" wire:click="sortList('{{ $column }}')" class="inline-flex items-center gap-1 hover:text-ink">
                                        {{ $label }}
                                        @if ($listSort === $column)
                                            <x-icon :icon="$listDirection === 'asc' ? 'chevron-up' : 'chevron-down'" class="size-3" />
                                        @endif
                                    </button>
                                </th>
                            @endforeach
                            <th class="px-4 py-2.5 font-medium">Etiquetas</th>
                            <th class="px-4 py-2.5 font-medium">Asignados</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($this->listTasks as $task)
                            <tr wire:key="row-{{ $task->id }}" class="hover:bg-hover">
                                <td class="max-w-sm px-4 py-2.5">
                                    <button type="button" wire:click="editTask({{ $task->id }})"
                                            @class(['block max-w-full truncate text-left hover:underline', 'text-ink-muted line-through' => $task->completed_at, 'text-ink' => ! $task->completed_at])>
                                        {{ $task->name }}
                                    </button>
                                    @if ($task->subtasks_count > 0)
                                        <span class="text-xs text-ink-subtle">{{ $task->closed_subtasks_count }}/{{ $task->subtasks_count }} subtareas</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5"><x-app.color-tag :color="$task->status->color" :text="$task->status->name" /></td>
                                <td class="px-4 py-2.5"><x-app.color-tag :color="$task->priority->color" :text="$task->priority->name" /></td>
                                <td class="px-4 py-2.5 whitespace-nowrap text-ink-muted tabular-nums">
                                    @if ($task->starts_on || $task->ends_on)
                                        {{ $task->starts_on?->translatedFormat('j M') ?? '…' }} – {{ $task->ends_on?->translatedFormat('j M') ?? '…' }}
                                    @else
                                        <span class="text-ink-subtle">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($task->labels as $label)
                                            <x-app.color-tag :color="$label->color" :text="$label->name" />
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-4 py-2.5">
                                    <span class="flex -space-x-1.5">
                                        @foreach ($task->assignees->take(4) as $assignee)
                                            <x-app.avatar :name="$assignee->name" class="size-6 ring-2 ring-surface" />
                                        @endforeach
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @else
        {{-- Calendar: month grid by task dates --}}
        <div class="space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <x-button.circle icon="chevron-left" sm flat color="secondary" wire:click="changeMonth(-1)" aria-label="Mes anterior" />
                <x-button.circle icon="chevron-right" sm flat color="secondary" wire:click="changeMonth(1)" aria-label="Mes siguiente" />
                <h3 class="text-base font-semibold text-ink first-letter:uppercase">{{ $this->calendarMonth()->translatedFormat('F Y') }}</h3>
                <x-button text="Hoy" sm flat color="secondary" wire:click="goToToday" />

                @if ($this->undatedCount > 0)
                    <span class="ml-auto text-xs text-ink-subtle">
                        {{ trans_choice('{1} 1 tarea sin fechas no aparece en el calendario|[2,*] :count tareas sin fechas no aparecen en el calendario', $this->undatedCount) }}
                    </span>
                @endif
            </div>

            <div class="overflow-x-auto rounded-lg border border-line bg-surface">
                <div class="grid min-w-[720px] grid-cols-7 border-b border-line text-xs text-ink-muted">
                    @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $weekday)
                        <div class="px-2 py-1.5 font-medium">{{ $weekday }}</div>
                    @endforeach
                </div>

                @foreach ($this->calendarWeeks as $week)
                    <div wire:key="week-{{ $week[0]['date']->format('Y-m-d') }}" class="grid min-w-[720px] grid-cols-7 divide-x divide-line border-b border-line last:border-b-0">
                        @foreach ($week as $day)
                            <div @class(['min-h-28 space-y-1 p-1.5', 'bg-surface-muted' => ! $day['inMonth']])>
                                <p @class([
                                    'flex size-6 items-center justify-center rounded-full text-xs tabular-nums',
                                    'bg-ink font-semibold text-canvas' => $day['isToday'],
                                    'text-ink' => ! $day['isToday'] && $day['inMonth'],
                                    'text-ink-subtle' => ! $day['isToday'] && ! $day['inMonth'],
                                ])>{{ $day['date']->day }}</p>

                                @foreach ($day['tasks']->take(3) as $task)
                                    <button type="button" wire:click="editTask({{ $task->id }})" wire:key="cal-{{ $day['date']->format('md') }}-{{ $task->id }}"
                                            title="{{ $task->name }}"
                                            @class(['flex w-full items-center gap-1 truncate rounded border border-line bg-surface px-1.5 py-0.5 text-left text-xs hover:bg-hover', 'text-ink-muted line-through' => $task->completed_at, 'text-ink' => ! $task->completed_at])>
                                        <span class="size-1.5 shrink-0 rounded-full bg-ink-subtle" @if ($task->status->color) style="background-color: {{ $task->status->color }}" @endif aria-hidden="true"></span>
                                        <span class="truncate">{{ $task->name }}</span>
                                    </button>
                                @endforeach

                                @if ($day['tasks']->count() > 3)
                                    <p class="px-1 text-xs text-ink-subtle">+{{ $day['tasks']->count() - 3 }} más</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Create a task, or open one from any view --}}
    @php($editingTask = $this->editingTask)
    <x-slide id="task-slide" wire="showTaskForm" :title="$editingTask ? 'Tarea' : 'Nueva tarea'" size="xl">
        @if ($editingTask)
            <div class="mb-5 flex flex-wrap items-center gap-2">
                @if ($editingTask->completed_at)
                    <x-badge text="Cerrada" color="green" light round />
                @endif
                @if ($editingTask->parent)
                    <span class="text-sm text-ink-muted">Subtarea de «{{ $editingTask->parent->name }}»</span>
                @endif
                <a href="{{ route('tasks.show', $editingTask) }}" wire:navigate
                   class="ml-auto inline-flex items-center gap-1 text-sm text-ink-muted hover:text-ink hover:underline">
                    Abrir página completa
                    <x-icon icon="arrow-top-right-on-square" class="size-4" />
                </a>
            </div>

            @unless ($this->canEditTask)
                <div class="mb-5 flex items-start gap-2 rounded-md border border-line bg-hover px-3 py-2 text-sm text-ink-muted">
                    <x-icon icon="lock-closed" class="mt-0.5 size-4 shrink-0" />
                    Puedes ver esta tarea, pero solo la editan los admins del proyecto y quienes la tienen asignada.
                </div>
            @endunless
        @endif

        <form id="task-form" wire:submit="saveTask" class="space-y-8">
            @include('livewire.tasks.partials.fields', [
                'readonly' => $editingTask !== null && ! $this->canEditTask,
                'editorKey' => $taskFormVersion,
            ])

            @unless ($editingTask)
                <x-upload label="Adjuntos" wire:model="pendingUploads" multiple delete
                          placeholder="Adjuntar archivos…" tip="Hasta 10 archivos de 10 MB cada uno" />
            @endunless
        </form>

        @if ($editingTask)
            <div class="mt-8">
                <livewire:tasks.attachments :task="$editingTask" wire:key="board-attachments-{{ $editingTask->id }}-{{ $taskFormVersion }}" />
            </div>

            <div class="mt-8">
                <livewire:comments.conversations :commentable="$editingTask" wire:key="board-comments-{{ $editingTask->id }}-{{ $taskFormVersion }}" />
            </div>
        @endif

        <x-slot:footer>
            <x-button flat color="secondary" :text="$editingTask && ! $this->canEditTask ? 'Cerrar' : 'Cancelar'" sm wire:click="$set('showTaskForm', false)" />
            @if (! $editingTask)
                <x-button type="submit" form="task-form" text="Crear tarea" sm loading="saveTask" />
            @elseif ($this->canEditTask)
                <x-button type="submit" form="task-form" text="Guardar cambios" sm loading="saveTask" />
            @endif
        </x-slot:footer>
    </x-slide>
</div>
