<x-ui.page>
    <x-ui.page-header title="Tareas" icon="✅"
                      description="Las tareas de todos tus proyectos en kanban, listado o calendario." />

    <div class="space-y-4" x-data="{ showFilters: @js($this->activeFilters > 0) }">
        {{-- Toolbar: view switcher, search, filters --}}
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
                      :text="'Filtros'.($this->activeFilters > 0 ? ' ('.$this->activeFilters.')' : '')" />
            <x-button icon="user" text="Mis tareas" sm flat color="secondary" wire:click="onlyMine" />
            <x-button wire:click="$toggle('onlyOverdue')" text="Solo vencidas" sm :color="$onlyOverdue ? 'primary' : 'secondary'" :flat="! $onlyOverdue" />
            <x-button wire:click="$toggle('hidePaused')" text="Ocultar pausados" sm :color="$hidePaused ? 'primary' : 'secondary'" :flat="! $hidePaused" />
            <x-button wire:click="$toggle('showClosed')" text="Mostrar cerradas" sm :color="$showClosed ? 'primary' : 'secondary'" :flat="! $showClosed" />

            @if ($this->activeFilters > 0 || $search !== '' || $showClosed)
                <x-button icon="x-mark" text="Limpiar" sm flat color="secondary" wire:click="clearFilters" />
            @endif
        </div>

        {{-- Filters (kept in the address) --}}
        <div x-show="showFilters" x-collapse x-cloak>
            <div class="grid gap-3 rounded-lg border border-line bg-surface p-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <x-select.styled label="Proyecto" wire:model.live="projectIds" :options="$this->projectOptions" multiple searchable placeholder="Todos" />
                <x-select.styled label="Cliente" wire:model.live="clientIds" :options="$this->clientOptions" multiple searchable placeholder="Todos" />
                <x-select.styled label="Asignado" wire:model.live="assigneeIds" :options="$this->assigneeOptions" multiple searchable placeholder="Todos" />
                <x-select.styled label="Estado" wire:model.live="categories" :options="$this->categoryOptions" multiple placeholder="Abiertos" />
                <x-input label="Desde" type="date" wire:model.live="dateFrom" />
                <x-input label="Hasta" type="date" wire:model.live="dateTo" />
            </div>
        </div>

        @if ($view === 'kanban')
            {{-- Kanban: one column per status category, drag to move --}}
            <div class="scrollbar-subtle -mx-1 flex gap-3 overflow-x-auto px-1 pb-2">
                @foreach ($this->shownCategories as $category)
                    @php($columnTasks = $this->tasks->filter(fn ($task) => $task->status->category === $category))
                    <section wire:key="column-{{ $category->value }}" class="flex w-72 shrink-0 flex-col rounded-lg bg-surface-muted p-2" aria-label="{{ $category->label() }}">
                        <header class="flex items-center gap-2 px-1.5 pb-2">
                            <h3 class="min-w-0 flex-1 truncate text-sm font-medium text-ink">{{ $category->label() }}</h3>
                            <span class="text-xs text-ink-subtle tabular-nums">{{ $columnTasks->count() }}</span>
                        </header>

                        <ul wire:sort="moveTask" wire:sort:group="tasks" wire:sort:group-id="{{ $category->value }}"
                            class="flex min-h-16 flex-1 flex-col gap-2">
                            @foreach ($columnTasks as $task)
                                <li wire:key="card-{{ $task->id }}" wire:sort:item="{{ $task->id }}"
                                    class="cursor-grab rounded-md border border-line bg-surface p-2.5 shadow-xs active:cursor-grabbing">
                                    <div class="mb-1 flex items-center gap-2 text-xs">
                                        <a href="{{ route('projects.show', $task->project) }}" wire:navigate
                                           class="min-w-0 flex-1 truncate text-ink-muted hover:text-ink hover:underline">{{ $task->project->icon }} {{ $task->project->name }}</a>
                                        <x-app.color-tag :color="$task->status->color" :text="$task->status->name" />
                                    </div>

                                    <a href="{{ route('tasks.show', $task) }}" wire:navigate
                                       @class(['block text-sm hover:underline', 'text-ink-muted line-through' => $task->completed_at, 'text-ink' => ! $task->completed_at])>
                                        {{ $task->name }}
                                    </a>

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

            @if ($this->tasks->isEmpty())
                <p class="text-sm text-ink-muted">Ninguna tarea cumple los filtros.</p>
            @endif
        @elseif ($view === 'list')
            {{-- List: sortable table --}}
            @if ($this->listTasks->isEmpty())
                <x-ui.empty-state icon="check-circle" title="Ninguna tarea cumple los filtros"
                                  description="Prueba con otros filtros o muestra también las tareas cerradas." />
            @else
                <div class="overflow-x-auto rounded-lg border border-line bg-surface">
                    <table class="w-full min-w-[900px] text-sm">
                        <thead>
                            <tr class="border-b border-line text-left text-xs text-ink-muted">
                                @foreach (['name' => 'Tarea', 'project' => 'Proyecto', 'status' => 'Estado', 'priority' => 'Prioridad', 'ends_on' => 'Fechas'] as $column => $label)
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
                                        <a href="{{ route('tasks.show', $task) }}" wire:navigate
                                           @class(['block truncate hover:underline', 'text-ink-muted line-through' => $task->completed_at, 'text-ink' => ! $task->completed_at])>
                                            {{ $task->name }}
                                        </a>
                                        @if ($task->subtasks_count > 0)
                                            <span class="text-xs text-ink-subtle">{{ $task->closed_subtasks_count }}/{{ $task->subtasks_count }} subtareas</span>
                                        @endif
                                    </td>
                                    <td class="max-w-56 px-4 py-2.5">
                                        <a href="{{ route('projects.show', $task->project) }}" wire:navigate
                                           class="block truncate text-ink-muted hover:text-ink hover:underline">{{ $task->project->icon }} {{ $task->project->name }}</a>
                                    </td>
                                    <td class="px-4 py-2.5"><x-app.color-tag :color="$task->status->color" :text="$task->status->name" /></td>
                                    <td class="px-4 py-2.5"><x-app.color-tag :color="$task->priority->color" :text="$task->priority->name" /></td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-ink-muted tabular-nums">
                                        @if ($task->starts_on || $task->ends_on)
                                            @php($isOverdue = ! $task->completed_at && $task->ends_on?->lt(today()))
                                            <span @class(['font-semibold text-overdue' => $isOverdue])>
                                                {{ $task->starts_on?->translatedFormat('j M') ?? '…' }} – {{ $task->ends_on?->translatedFormat('j M') ?? '…' }}
                                            </span>
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
            {{-- Calendar: month grid by task dates, plus project due dates --}}
            <div class="space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <x-button.circle icon="chevron-left" sm flat color="secondary" wire:click="changeMonth(-1)" aria-label="Mes anterior" />
                    <x-button.circle icon="chevron-right" sm flat color="secondary" wire:click="changeMonth(1)" aria-label="Mes siguiente" />
                    <h3 class="text-base font-semibold text-ink first-letter:uppercase">{{ $this->calendarMonth()->translatedFormat('F Y') }}</h3>
                    <x-button text="Hoy" sm flat color="secondary" wire:click="goToToday" />

                    <span class="ml-auto flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-ink-subtle">
                        <span class="flex items-center gap-1"><x-icon icon="flag" class="size-3.5" /> Término de proyecto</span>
                        @if ($this->undatedCount > 0)
                            <span>{{ trans_choice('{1} 1 tarea sin fechas no aparece en el calendario|[2,*] :count tareas sin fechas no aparecen en el calendario', $this->undatedCount) }}</span>
                        @endif
                    </span>
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
                                @php($dueProjects = $this->projectDueDates->get($day['date']->toDateString(), collect()))
                                <div @class(['min-h-28 space-y-1 p-1.5', 'bg-surface-muted' => ! $day['inMonth']])>
                                    <p @class([
                                        'flex size-6 items-center justify-center rounded-full text-xs tabular-nums',
                                        'bg-ink font-semibold text-canvas' => $day['isToday'],
                                        'text-ink' => ! $day['isToday'] && $day['inMonth'],
                                        'text-ink-subtle' => ! $day['isToday'] && ! $day['inMonth'],
                                    ])>{{ $day['date']->day }}</p>

                                    @foreach ($dueProjects as $dueProject)
                                        <a href="{{ route('projects.show', $dueProject) }}" wire:navigate wire:key="due-{{ $day['date']->format('md') }}-{{ $dueProject->id }}"
                                           title="Término de {{ $dueProject->name }}"
                                           class="flex items-center gap-1 truncate rounded px-1.5 py-0.5 text-xs font-medium text-ink-muted hover:bg-hover hover:text-ink">
                                            <x-icon icon="flag" class="size-3 shrink-0" />
                                            <span class="truncate">{{ $dueProject->icon }} {{ $dueProject->name }}</span>
                                        </a>
                                    @endforeach

                                    @foreach ($day['tasks']->take(4) as $task)
                                        <a href="{{ route('tasks.show', $task) }}" wire:navigate wire:key="cal-{{ $day['date']->format('md') }}-{{ $task->id }}"
                                           title="{{ $task->project->name }} · {{ $task->name }}"
                                           @class(['flex items-center gap-1 truncate rounded border border-line bg-surface px-1.5 py-0.5 text-xs hover:bg-hover', 'text-ink-muted line-through' => $task->completed_at, 'text-ink' => ! $task->completed_at])>
                                            <span class="size-1.5 shrink-0 rounded-full bg-ink-subtle" @if ($task->status->color) style="background-color: {{ $task->status->color }}" @endif aria-hidden="true"></span>
                                            @if ($task->project->icon)
                                                <span class="shrink-0">{{ $task->project->icon }}</span>
                                            @endif
                                            <span class="truncate">{{ $task->name }}</span>
                                        </a>
                                    @endforeach

                                    @if ($day['tasks']->count() > 4)
                                        <p class="px-1 text-xs text-ink-subtle">+{{ $day['tasks']->count() - 4 }} más</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-ui.page>
