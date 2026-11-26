@php
    $client = $project->client;
    $tabs = [
        ...collect(\App\Enums\TaskView::cases())->mapWithKeys(fn ($taskView) => [$taskView->value => ['label' => $taskView->label(), 'icon' => $taskView->icon()]])->all(),
        \App\Livewire\Portal\ProjectShow::DOCS => ['label' => 'Documentación', 'icon' => 'book-open'],
    ];
@endphp

<x-ui.page>
    <x-ui.page-header :title="$project->name" :icon="$project->icon" :description="$project->description"
                      :breadcrumbs="[['label' => 'Inicio', 'link' => route('portal.index')], ['label' => $project->name]]">
        <x-slot:actions>
            <span class="flex items-center gap-2 text-sm text-ink-muted">
                <x-app.client-logo :client="$client" size="sm" />
                {{ $client->name }}
            </span>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Summary --}}
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-line bg-surface p-4 sm:col-span-2">
            @include('livewire.portal.partials.progress', ['closed' => $this->progress['closed'], 'total' => $this->progress['total']])
        </div>
        <div class="rounded-xl border border-line bg-surface p-4">
            <p class="text-xs text-ink-muted">Tareas vencidas</p>
            <p @class(['mt-1 text-2xl font-semibold tabular-nums', 'text-overdue' => $this->progress['overdue'] > 0, 'text-ink' => $this->progress['overdue'] === 0])>{{ $this->progress['overdue'] }}</p>
        </div>
        <div class="rounded-xl border border-line bg-surface p-4">
            <p class="text-xs text-ink-muted">Fecha de término</p>
            <p class="mt-1 text-2xl font-semibold text-ink first-letter:uppercase">
                {{ $project->due_on?->translatedFormat('j M Y') ?? '—' }}
            </p>
            @if ($project->paused_at)
                <p class="mt-0.5 text-xs text-ink-muted">Proyecto en pausa</p>
            @endif
        </div>
    </div>

    <div class="space-y-4">
        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-2">
            <div class="inline-flex flex-wrap items-center gap-1 rounded-lg bg-hover p-1" role="tablist" aria-label="Vista del proyecto">
                @foreach ($tabs as $value => $tab)
                    <button type="button" role="tab" wire:click="setView('{{ $value }}')"
                            aria-selected="{{ $view === $value ? 'true' : 'false' }}"
                            @class([
                                'inline-flex h-7 items-center gap-1.5 rounded-md px-2.5 text-sm transition-colors',
                                'bg-surface font-medium text-ink shadow-xs' => $view === $value,
                                'text-ink-muted hover:text-ink' => $view !== $value,
                            ])>
                        <x-icon :icon="$tab['icon']" class="size-4" />
                        {{ $tab['label'] }}
                        @if ($value === \App\Livewire\Portal\ProjectShow::DOCS && $this->pages->isNotEmpty())
                            <span class="text-xs text-ink-subtle tabular-nums">{{ $this->pages->count() }}</span>
                        @endif
                    </button>
                @endforeach
            </div>

            @if ($view !== \App\Livewire\Portal\ProjectShow::DOCS)
                <div class="w-full max-w-xs sm:w-56">
                    <x-input icon="magnifying-glass" placeholder="Buscar tareas…" wire:model.live.debounce.400ms="search" />
                </div>
            @endif
        </div>

        @if ($view === 'kanban')
            <div class="scrollbar-subtle -mx-1 flex gap-3 overflow-x-auto px-1 pb-2">
                @foreach ($this->columns as $status)
                    <section wire:key="portal-column-{{ $status->id }}" class="flex w-72 shrink-0 flex-col rounded-xl bg-surface-muted p-2" aria-label="{{ $status->name }}">
                        <header class="flex items-center gap-2 px-1.5 pb-2">
                            <span class="size-2 shrink-0 rounded-full bg-ink-subtle" @if ($status->color) style="background-color: {{ $status->color }}" @endif aria-hidden="true"></span>
                            <h3 class="min-w-0 flex-1 truncate text-sm font-medium text-ink first-letter:uppercase">{{ $status->name }}</h3>
                            <span class="text-xs text-ink-subtle tabular-nums">{{ $status->tasks->count() }}</span>
                        </header>

                        <ul class="flex min-h-16 flex-1 flex-col gap-2">
                            @foreach ($status->tasks as $task)
                                @include('livewire.portal.partials.task-card', ['task' => $task])
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>

            @if ($this->tasks->isEmpty())
                <p class="text-sm text-ink-muted">{{ $search !== '' ? 'Ninguna tarea coincide con la búsqueda.' : 'Este proyecto todavía no tiene tareas.' }}</p>
            @endif
        @elseif ($view === 'list')
            @if ($this->listTasks->isEmpty())
                <x-ui.empty-state icon="check-circle" :title="$search !== '' ? 'Ninguna tarea coincide con la búsqueda' : 'Este proyecto todavía no tiene tareas'" />
            @else
                <div class="overflow-x-auto rounded-xl border border-line bg-surface">
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
                                <th class="px-4 py-2.5 font-medium">Responsables</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($this->listTasks as $task)
                                <tr wire:key="portal-row-{{ $task->id }}" wire:click="openTask('{{ $task->uuid }}')" class="cursor-pointer hover:bg-hover">
                                    <td class="max-w-md px-4 py-2.5">
                                        <span @class(['block truncate', 'text-ink-muted line-through' => $task->completed_at, 'font-medium text-ink' => ! $task->completed_at])>{{ $task->name }}</span>
                                        <span class="flex gap-3 text-xs text-ink-subtle">
                                            @if ($task->subtasks_count > 0)
                                                <span>{{ $task->closed_subtasks_count }}/{{ $task->subtasks_count }} subtareas</span>
                                            @endif
                                            @if ($task->client_comments_count > 0)
                                                <span>{{ trans_choice('{1} 1 mensaje|[2,*] :count mensajes', $task->client_comments_count) }}</span>
                                            @endif
                                        </span>
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
        @elseif ($view === 'calendar')
            <div class="space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <x-button.circle icon="chevron-left" sm flat color="secondary" wire:click="changeMonth(-1)" aria-label="Mes anterior" />
                    <x-button.circle icon="chevron-right" sm flat color="secondary" wire:click="changeMonth(1)" aria-label="Mes siguiente" />
                    <h3 class="text-base font-semibold text-ink first-letter:uppercase">{{ $this->calendarMonth->translatedFormat('F Y') }}</h3>
                    <x-button text="Hoy" sm flat color="secondary" wire:click="goToToday" />

                    @if ($this->undatedCount > 0)
                        <span class="ml-auto text-xs text-ink-subtle">
                            {{ trans_choice('{1} 1 tarea sin fechas no aparece en el calendario|[2,*] :count tareas sin fechas no aparecen en el calendario', $this->undatedCount) }}
                        </span>
                    @endif
                </div>

                <div class="overflow-x-auto rounded-xl border border-line bg-surface">
                    <div class="grid min-w-[720px] grid-cols-7 border-b border-line text-xs text-ink-muted">
                        @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $weekday)
                            <div class="px-2 py-1.5 font-medium">{{ $weekday }}</div>
                        @endforeach
                    </div>

                    @foreach ($this->calendarWeeks as $week)
                        <div wire:key="portal-week-{{ $week[0]['date']->format('Y-m-d') }}" class="grid min-w-[720px] grid-cols-7 divide-x divide-line border-b border-line last:border-b-0">
                            @foreach ($week as $day)
                                <div @class(['min-h-28 space-y-1 p-1.5', 'bg-surface-muted' => ! $day['inMonth']])>
                                    <p @class([
                                        'flex size-6 items-center justify-center rounded-full text-xs tabular-nums',
                                        'bg-ink font-semibold text-canvas' => $day['isToday'],
                                        'text-ink' => ! $day['isToday'] && $day['inMonth'],
                                        'text-ink-subtle' => ! $day['isToday'] && ! $day['inMonth'],
                                    ])>{{ $day['date']->day }}</p>

                                    @if ($project->due_on?->isSameDay($day['date']))
                                        <p class="flex items-center gap-1 truncate rounded px-1.5 py-0.5 text-xs font-medium text-ink-muted" title="Término del proyecto">
                                            <x-icon icon="flag" class="size-3 shrink-0" /> Término
                                        </p>
                                    @endif

                                    @foreach ($day['tasks']->take(4) as $task)
                                        <button type="button" wire:click="openTask('{{ $task->uuid }}')" wire:key="portal-cal-{{ $day['date']->format('md') }}-{{ $task->id }}"
                                                title="{{ $task->name }}"
                                                @class(['flex w-full items-center gap-1 truncate rounded border border-line bg-surface px-1.5 py-0.5 text-left text-xs hover:bg-hover', 'text-ink-muted line-through' => $task->completed_at, 'text-ink' => ! $task->completed_at])>
                                            <span class="size-1.5 shrink-0 rounded-full bg-ink-subtle" @if ($task->status->color) style="background-color: {{ $task->status->color }}" @endif aria-hidden="true"></span>
                                            <span class="truncate">{{ $task->name }}</span>
                                        </button>
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
        @else
            @if ($this->pages->isEmpty())
                <x-ui.empty-state icon="book-open" title="Aún no hay documentación compartida"
                                  description="Cuando el equipo comparta páginas de este proyecto, las verás aquí." />
            @else
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($this->pages as $page)
                        <a href="{{ route('portal.pages.show', $page) }}" wire:navigate wire:key="portal-doc-{{ $page->id }}"
                           class="group flex items-start gap-3 rounded-xl border border-line bg-surface p-4 transition hover:-translate-y-0.5 hover:border-line-strong hover:shadow-sm">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-hover text-lg" aria-hidden="true">{{ $page->icon ?? '📄' }}</span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-medium text-ink group-hover:underline">{{ $page->title }}</span>
                                <span class="block text-xs text-ink-subtle">Actualizada {{ $page->updated_at->diffForHumans() }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        @endif
    </div>

    <x-slide id="portal-task-slide" wire="showTask" title="Tarea" size="xl">
        @if ($this->openedTask)
            <livewire:portal.task-panel :task="$this->openedTask" wire:key="portal-task-{{ $this->openedTask->id }}" />
        @endif

        <x-slot:footer>
            <x-button flat color="secondary" text="Cerrar" sm wire:click="closeTask" />
        </x-slot:footer>
    </x-slide>
</x-ui.page>
