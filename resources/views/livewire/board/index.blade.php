<x-ui.page wire:poll.visible.45s>
    @unless ($focus)
    <x-ui.page-header title="Pizarra" icon="📋"
                      :description="'Semana del '.$this->weekLabel.'. Notas compartidas de lo que hay que hacer cada día.'">
        <x-slot:actions>
            <x-button icon="chevron-left" text="Anterior" sm flat wire:click="previousWeek" />
            <x-button text="Hoy" sm flat wire:click="currentWeek" />
            <x-button icon="chevron-right" text="Siguiente" sm flat wire:click="nextWeek" />
            <x-button icon="arrows-pointing-out" text="Sin distracciones" sm flat wire:click="$set('focus', true)" />
        </x-slot:actions>
    </x-ui.page-header>

    <div class="flex flex-wrap items-center gap-2">
        <div class="w-44">
            <x-input wire:model.live.debounce.300ms="search" placeholder="Buscar proyecto…" />
        </div>
        <x-toggle wire:model.live="weekend" label="Fin de semana" />
        <x-button wire:click="$toggle('hidePaused')" text="Ocultar pausados" sm :color="$hidePaused ? 'primary' : 'secondary'" :flat="! $hidePaused" />
        <x-button wire:click="$toggle('onlyOverdue')" text="Solo vencidos" sm :color="$onlyOverdue ? 'primary' : 'secondary'" :flat="! $onlyOverdue" />
        <x-button wire:click="$toggle('idleToday')" text="No trabajados hoy" sm :color="$idleToday ? 'primary' : 'secondary'" :flat="! $idleToday" />
        @if ($this->hiddenProjects->isNotEmpty())
            <x-button wire:click="$toggle('showHidden')" text="Ocultos ({{ $this->hiddenProjects->count() }})" sm flat />
        @endif
    </div>

    @if ($showHidden && $this->hiddenProjects->isNotEmpty())
        <ul class="flex flex-wrap gap-2">
            @foreach ($this->hiddenProjects as $hidden)
                <li wire:key="hidden-{{ $hidden->id }}" class="flex items-center gap-2 rounded-full border border-line bg-surface px-2 py-1 text-xs text-ink">
                    <span>{{ $hidden->icon }} {{ $hidden->name }}</span>
                    <button type="button" wire:click="showProject('{{ $hidden->uuid }}')" class="text-ink-muted hover:text-ink">Mostrar</button>
                </li>
            @endforeach
        </ul>
    @endif

    <ul class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-ink-muted">
        <li class="flex items-center gap-1.5"><span class="size-2 rounded-sm border border-line bg-surface"></span> Planificada</li>
        <li class="flex items-center gap-1.5"><x-icon icon="check" class="size-3 text-priority-medium" /> Hecha</li>
        <li class="flex items-center gap-1.5"><span class="size-2 rounded-sm border border-overdue bg-surface"></span> Vencida</li>
        <li class="flex items-center gap-1.5"><x-icon icon="pause" class="size-3 text-ink-subtle" /> En pausa (fila atenuada)</li>
        <li class="flex items-center gap-1.5"><span class="size-1 rounded-full bg-ink-subtle"></span> Sin trabajo</li>
    </ul>
    @endunless

    <div @if ($focus)
             x-data="{
                 init() { document.documentElement.style.overflow = 'hidden' },
                 destroy() { document.documentElement.style.overflow = '' },
             }"
             x-on:keydown.escape.window="if (! $event.target.closest('input, textarea, select')) { $wire.set('focus', false) }"
             role="dialog" aria-modal="true" aria-label="Pizarra sin distracciones"
         @endif
         @class(['fixed inset-0 z-50 flex flex-col bg-canvas' => $focus])>
    @if ($focus)
        <div class="flex shrink-0 flex-wrap items-center gap-x-2 gap-y-1 px-3 py-1.5">
            <p class="min-w-0 flex-1 truncate text-sm text-ink">
                <span class="font-medium">📋 Pizarra</span>
                <span class="text-ink-muted">· Semana del {{ $this->weekLabel }}</span>
            </p>
            <x-button icon="chevron-left" text="Anterior" sm flat wire:click="previousWeek" />
            <x-button text="Hoy" sm flat wire:click="currentWeek" />
            <x-button icon="chevron-right" text="Siguiente" sm flat wire:click="nextWeek" />
            <div x-data="{ full: !! document.fullscreenElement }" x-on:fullscreenchange.document="full = !! document.fullscreenElement" x-show="document.fullscreenEnabled">
                <x-button sm flat x-on:click="full ? document.exitFullscreen() : document.documentElement.requestFullscreen()">
                    <span x-text="full ? 'Salir de pantalla completa' : 'Pantalla completa'">Pantalla completa</span>
                </x-button>
            </div>
            <x-button icon="arrows-pointing-in" text="Salir" sm flat title="Salir de la vista sin distracciones (Esc)"
                      x-on:click="document.fullscreenElement && document.exitFullscreen()" wire:click="$set('focus', false)" />
        </div>
    @endif

    @if ($this->rows->isEmpty())
        <x-ui.empty-state icon="view-columns" title="Nada que mostrar"
                          description="No hay proyectos que coincidan con estos filtros. Los que ocultaste siguen en «Ocultos»." />
    @else
        <div @class([
                 'overflow-auto overscroll-contain bg-canvas scrollbar-subtle',
                 'max-h-[calc(100dvh-16rem)] rounded-lg border border-line' => ! $focus,
                 'min-h-0 flex-1 border-t border-line' => $focus,
             ])>
            <table @if ($this->canReorder) wire:sort="reorderProjects" @endif class="w-full table-fixed border-separate border-spacing-0 text-[11px]">
                <thead>
                    <tr>
                        <th class="sticky top-0 left-0 z-30 w-56 border-b border-r border-line bg-canvas px-2 py-1.5 text-left font-medium text-ink-muted">
                            Proyecto
                        </th>
                        @foreach ($this->days as $day)
                            <th wire:key="head-{{ $day->toDateString() }}"
                                @class([
                                    'sticky top-0 z-20 border-b border-r border-line px-1 py-1.5 text-center font-medium',
                                    'bg-today text-ink' => $day->isToday(),
                                    'bg-canvas text-ink-muted' => ! $day->isToday(),
                                ])>
                                <span class="block">{{ $this->weekday($day) }}</span>
                                <span class="block text-sm font-semibold text-ink">{{ $day->format('j') }}</span>
                                @if ($day->isToday())
                                    <span class="sr-only">Hoy</span>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                @foreach ($this->rows as $row)
                    @php($project = $row['project'])
                    <tbody wire:key="board-project-{{ $project->id }}"
                           @if ($this->canReorder) wire:sort:item="{{ $project->id }}" @endif
                           x-data="{ open: $persist(false).as('wb-board-{{ $project->uuid }}') }"
                           data-project="{{ $project->name }}" data-project-overdue="{{ $project->isOverdue() ? '1' : '0' }}" data-open-overdue="{{ $row['openTasks'] }}" data-paused="{{ $project->isPaused() ? '1' : '0' }}"
                           class="group">
                        <tr>
                            <th class="sticky left-0 z-10 w-56 border-b border-r border-line bg-canvas py-1.5 pr-2 pl-3.5 text-left align-top font-normal">
                                @if ($this->canReorder)
                                    {{-- Drag handle in the left gutter: shown on hover, takes no room from the name. --}}
                                    <button type="button" wire:sort:handle
                                            class="absolute inset-y-0 left-0 z-[1] flex w-3.5 cursor-grab justify-center pt-2 text-ink-subtle opacity-0 transition-opacity group-hover:opacity-100 hover:text-ink focus-visible:opacity-100 active:cursor-grabbing"
                                            title="Arrastrar para reordenar" aria-label="Reordenar {{ $project->name }}">
                                        <x-icon icon="ellipsis-vertical" class="size-3.5" />
                                    </button>
                                @endif
                                <div class="relative">
                                    <div class="flex items-start gap-1.5">
                                        @if ($this->canReorder)
                                            <div class="relative mt-1 shrink-0" x-data="{ menu: false }" @click.outside="menu = false">
                                                <button type="button" @click="menu = !menu" class="-m-0.5 block rounded-full p-0.5 hover:bg-hover" aria-label="Prioridad: {{ $project->priority->label() }}">
                                                    <span class="block size-2.5 rounded-full {{ $project->priority->dotClass() }}"></span>
                                                </button>
                                                <div x-show="menu" x-cloak class="absolute left-0 z-40 mt-1 w-32 rounded-md border border-line bg-elevated p-1 shadow-sm">
                                                    @foreach (\App\Enums\ProjectPriorityLevel::cases() as $level)
                                                        <button type="button" wire:click="setPriority('{{ $project->uuid }}', '{{ $level->value }}')" @click="menu = false"
                                                                class="flex w-full items-center gap-2 rounded px-1.5 py-1 text-left text-xs text-ink hover:bg-hover">
                                                            <span class="size-2.5 rounded-full {{ $level->dotClass() }}"></span>
                                                            {{ $level->label() }}
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @else
                                            <span class="mt-1 block size-2.5 shrink-0 rounded-full {{ $project->priority->dotClass() }}" title="Prioridad: {{ $project->priority->label() }}"></span>
                                        @endif
                                        <a href="{{ route('projects.show', $project) }}" wire:navigate title="{{ $project->name }}"
                                           @class([
                                               'line-clamp-2 min-w-0 flex-1 text-xs leading-snug font-medium hover:underline',
                                               'text-ink' => ! $project->isPaused(),
                                               'text-ink-muted' => $project->isPaused(),
                                           ])>{{ $project->icon }} {{ $project->name }}</a>
                                    </div>

                                    <div class="mt-1 flex flex-wrap items-center gap-x-1.5 gap-y-0.5 text-[10px]">
                                        @if ($project->isPaused())
                                            <span class="inline-flex items-center gap-0.5 rounded border border-line bg-surface px-1 font-medium text-ink-muted">
                                                <x-icon icon="pause" class="size-3" />
                                                En pausa
                                            </span>
                                        @endif
                                        @if ($this->canReorder)
                                            <span x-data="{ editing: false }" class="inline-flex">
                                                <button type="button" x-show="!editing" x-on:click="editing = true; $nextTick(() => $refs.due.focus())"
                                                        class="inline-flex items-center gap-0.5 text-ink-muted hover:text-ink" title="Fecha de término">
                                                    <x-icon icon="calendar" class="size-3" />
                                                    {{ $project->due_on ? $project->due_on->translatedFormat('j M Y') : 'Término' }}
                                                </button>
                                                <input x-show="editing" x-cloak x-ref="due" type="date" value="{{ $project->due_on?->toDateString() }}"
                                                       x-on:change="$wire.setDueDate('{{ $project->uuid }}', $event.target.value); editing = false"
                                                       x-on:blur="editing = false"
                                                       class="w-28 border-0 bg-transparent p-0 text-[10px] text-ink-muted"
                                                       aria-label="Fecha de término de {{ $project->name }}" />
                                            </span>
                                        @elseif ($project->due_on)
                                            <time class="inline-flex items-center gap-0.5 text-ink-muted" datetime="{{ $project->due_on->toDateString() }}">
                                                <x-icon icon="calendar" class="size-3" />
                                                {{ $project->due_on->translatedFormat('j M') }}
                                            </time>
                                        @endif
                                        @if ($project->isOverdue())
                                            <span class="rounded bg-surface px-1 font-medium text-overdue">Vencido</span>
                                        @endif
                                        @if ($row['overdueEntries'] > 0)
                                            <span class="text-overdue" title="Notas planificadas de días anteriores">{{ $row['overdueEntries'] }} {{ $row['overdueEntries'] === 1 ? 'nota' : 'notas' }}</span>
                                        @endif
                                    </div>

                                    <div class="mt-0.5 flex items-center gap-2 text-[10px] text-ink-subtle">
                                        @if ($row['tasks'] !== [])
                                            <button type="button" @click="open = !open" :aria-expanded="open"
                                                    class="-ml-0.5 inline-flex items-center gap-0.5 rounded text-ink-muted hover:text-ink">
                                                <span class="inline-flex transition-transform" :class="open && 'rotate-90'">
                                                    <x-icon icon="chevron-right" class="size-3" />
                                                </span>
                                                {{ count($row['tasks']) }} {{ count($row['tasks']) === 1 ? 'tarea' : 'tareas' }}
                                                <span class="sr-only">de {{ $project->name }}</span>
                                            </button>
                                        @endif
                                        @if ($row['openTasks'] > 0)
                                            <span class="text-overdue" title="Tareas abiertas con término vencido">{{ $row['openTasks'] }} {{ $row['openTasks'] === 1 ? 'vencida' : 'vencidas' }}</span>
                                        @endif
                                        <span class="ml-auto flex gap-2">
                                            @if ($this->canReorder)
                                                <button type="button" wire:click="togglePause('{{ $project->uuid }}')" class="hover:text-ink">
                                                    {{ $project->isPaused() ? 'Reanudar' : 'Pausar' }}
                                                </button>
                                            @endif
                                            <button type="button" wire:click="hideProject('{{ $project->uuid }}')" class="hover:text-ink">Ocultar</button>
                                        </span>
                                    </div>
                                </div>
                            </th>
                            @foreach ($this->days as $day)
                                @php($cell = $row['cells'][$day->toDateString()])
                                @include('livewire.board.partials.cell', [
                                    'project' => $project,
                                    'date' => $day->toDateString(),
                                    'entries' => $cell['entries'],
                                    'idle' => $cell['idle'],
                                    'taskUuid' => '',
                                    'choices' => $row['choices'],
                                    'onProjectRow' => true,
                                ])
                            @endforeach
                        </tr>
                        @foreach ($row['tasks'] as $taskRow)
                            <tr wire:key="board-task-{{ $taskRow['task']->id }}" x-show="open" x-cloak class="bg-surface-muted">
                                <th class="sticky left-0 z-10 border-b border-r border-line bg-surface-muted py-1 pr-2 pl-7 text-left align-top font-normal">
                                    <span title="{{ $taskRow['task']->name }}" @class(['line-clamp-2 text-[11px] leading-snug text-ink', 'text-overdue' => $taskRow['overdue']])>
                                        {{ $taskRow['task']->name }}
                                    </span>
                                    @if ($taskRow['overdue'])
                                        <span class="text-[10px] text-overdue">Tarea vencida</span>
                                    @endif
                                </th>
                                @foreach ($this->days as $day)
                                    @include('livewire.board.partials.cell', [
                                        'project' => $project,
                                        'date' => $day->toDateString(),
                                        'entries' => $taskRow['cells'][$day->toDateString()],
                                        'idle' => false,
                                        'taskUuid' => $taskRow['task']->uuid,
                                        'choices' => $row['choices'],
                                        'onProjectRow' => false,
                                    ])
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                @endforeach
            </table>
        </div>
    @endif
    </div>
</x-ui.page>
