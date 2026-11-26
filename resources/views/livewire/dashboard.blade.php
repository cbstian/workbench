<x-ui.page>
    @if ($this->workspace === null)
        <x-ui.page-header :title="$this->greeting" :description="str(now()->translatedFormat('l j \\d\\e F'))->ucfirst()" />

        @can('manage-account')
            <x-ui.empty-state icon="squares-2x2" title="Crea tu primer espacio de trabajo" description="Dentro de él agregarás clientes, proyectos y los usuarios que trabajan en ellos.">
                <x-button icon="plus" text="Nuevo espacio" sm :href="route('workspaces.index')" wire:navigate />
            </x-ui.empty-state>
        @else
            <x-ui.empty-state icon="squares-2x2" title="Aún no tienes espacios de trabajo" description="Cuando te inviten a uno, aparecerá aquí." />
        @endcan
    @elseif ($this->isClientUser)
        <x-ui.page-header :title="$this->greeting" :description="str(now()->translatedFormat('l j \\d\\e F'))->ucfirst()" />

        <x-ui.empty-state icon="eye-slash" title="Por ahora no hay contenido para ti"
                          :description="'Tu acceso a '.$this->workspace->name.' está listo'.($this->clientNames !== [] ? ' como cliente de '.implode(', ', $this->clientNames) : '').'. Cuando se defina qué pueden ver los clientes, lo encontrarás aquí.'" />
    @else
    <div wire:poll.visible.60s class="space-y-8">
        <x-ui.page-header :title="$this->greeting" :description="$this->summary">
            <x-slot:actions>
                <div class="inline-flex rounded-md border border-line bg-surface p-0.5 text-xs" role="group" aria-label="Qué tareas mostrar">
                    <button type="button" wire:click="$set('onlyMine', false)" aria-pressed="{{ $onlyMine ? 'false' : 'true' }}"
                            @class(['rounded px-2.5 py-1 font-medium transition-colors', 'bg-active text-ink' => ! $onlyMine, 'text-ink-muted hover:text-ink' => $onlyMine])>
                        Todo el equipo
                    </button>
                    <button type="button" wire:click="$set('onlyMine', true)" aria-pressed="{{ $onlyMine ? 'true' : 'false' }}"
                            @class(['rounded px-2.5 py-1 font-medium transition-colors', 'bg-active text-ink' => $onlyMine, 'text-ink-muted hover:text-ink' => ! $onlyMine])>
                        Solo mías
                    </button>
                </div>
                <x-button icon="queue-list" text="Ver tareas" sm flat color="secondary" :href="route('tasks.index')" wire:navigate />
            </x-slot:actions>
        </x-ui.page-header>

        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4" aria-label="Resumen">
            @foreach ($this->stats as $stat)
                <x-ui.stat :label="$stat['label']" :value="$stat['value']" :hint="$stat['hint']" :href="$stat['href']" :tone="$stat['tone']" />
            @endforeach
        </section>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <x-ui.section title="Atrasadas" description="Primero las de mayor prioridad.">
                    @if ($this->overdueTasks->count() > \App\Livewire\Dashboard::LIST_LIMIT)
                        <x-slot:actions>
                            <a href="{{ $this->stats[0]['href'] }}" wire:navigate class="text-ink-muted underline-offset-4 hover:text-ink hover:underline">
                                Ver las {{ $this->overdueTasks->count() }}
                            </a>
                        </x-slot:actions>
                    @endif

                    @if ($this->overdueTasks->isEmpty())
                        <x-ui.empty-state icon="check-badge" title="Nada atrasado" description="Todas las tareas con plazo vencido están cerradas." class="py-8" />
                    @else
                        <ul class="divide-y divide-line rounded-lg border border-overdue/30 bg-surface">
                            @foreach ($this->overdueTasks->take(\App\Livewire\Dashboard::LIST_LIMIT) as $task)
                                @include('livewire.dashboard.partials.task-row')
                            @endforeach
                        </ul>
                    @endif
                </x-ui.section>

                <x-ui.section title="Para hoy" :description="str(today()->translatedFormat('l j \\d\\e F'))->ucfirst()">
                    @if ($this->todayTasks->count() > \App\Livewire\Dashboard::LIST_LIMIT)
                        <x-slot:actions>
                            <a href="{{ $this->stats[1]['href'] }}" wire:navigate class="text-ink-muted underline-offset-4 hover:text-ink hover:underline">
                                Ver las {{ $this->todayTasks->count() }}
                            </a>
                        </x-slot:actions>
                    @endif

                    @if ($this->todayTasks->isEmpty() && $this->todayBoardEntries->isEmpty())
                        <x-ui.empty-state icon="sun" title="Nada vence hoy" description="Revisa lo que viene en los próximos días." class="py-8" />
                    @else
                        @if ($this->todayTasks->isNotEmpty())
                            <ul class="divide-y divide-line rounded-lg border border-line bg-surface">
                                @foreach ($this->todayTasks->take(\App\Livewire\Dashboard::LIST_LIMIT) as $task)
                                    @include('livewire.dashboard.partials.task-row')
                                @endforeach
                            </ul>
                        @endif

                        @if ($this->todayBoardEntries->isNotEmpty())
                            <div class="rounded-lg border border-dashed border-line-strong px-4 py-3">
                                <div class="mb-2 flex items-center justify-between gap-2">
                                    <p class="text-xs font-medium text-ink-muted">Notas de la pizarra para hoy</p>
                                    <a href="{{ route('board.index') }}" wire:navigate class="text-xs text-ink-muted underline-offset-4 hover:text-ink hover:underline">Abrir pizarra</a>
                                </div>
                                <ul class="space-y-1.5">
                                    @foreach ($this->todayBoardEntries as $entry)
                                        <li wire:key="entry-{{ $entry->id }}" class="flex items-baseline gap-2 text-sm">
                                            <x-icon icon="pencil-square" class="size-3.5 shrink-0 self-center text-ink-subtle" />
                                            <span class="min-w-0 truncate text-ink">{{ $entry->body }}</span>
                                            <span class="shrink-0 truncate text-xs text-ink-muted">{{ $entry->project->icon }} {{ $entry->project->name }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endif
                </x-ui.section>
            </div>

            <x-ui.section title="Requiere atención">
                @if ($this->attention === [])
                    <x-ui.empty-state icon="shield-check" title="Todo en orden" description="No hay tareas urgentes sin plazo, sin responsable o detenidas." class="py-8" />
                @else
                    <div class="space-y-3">
                        @foreach ($this->attention as $warning)
                            <div wire:key="warning-{{ $warning['key'] }}" class="rounded-lg border border-line bg-surface p-4">
                                <div class="flex items-start gap-3">
                                    <x-icon icon="exclamation-triangle" class="mt-0.5 size-4 shrink-0 text-overdue" />
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-baseline justify-between gap-2">
                                            <p class="text-sm font-medium text-ink">{{ $warning['title'] }}</p>
                                            <span class="font-display text-lg font-semibold text-ink tabular-nums">{{ $warning['count'] }}</span>
                                        </div>
                                        <p class="text-xs text-ink-muted">{{ $warning['description'] }}</p>
                                        <ul class="mt-2 space-y-1">
                                            @foreach ($warning['examples'] as $example)
                                                <li class="flex items-baseline justify-between gap-2 text-sm">
                                                    <a href="{{ $example['href'] }}" wire:navigate class="min-w-0 truncate text-ink hover:underline">{{ $example['label'] }}</a>
                                                    <span class="shrink-0 truncate text-xs text-ink-subtle">{{ $example['meta'] }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                        @if ($warning['count'] > count($warning['examples']))
                                            <a href="{{ $warning['href'] }}" wire:navigate class="mt-2 inline-block text-xs text-ink-muted underline-offset-4 hover:text-ink hover:underline">
                                                Ver todas
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.section>
        </div>

        <x-ui.section :title="'Próximos '.\App\Livewire\Dashboard::UPCOMING_DAYS.' días'" description="Lo que vence después de hoy, día por día.">
            @if ($this->upcomingTasks->isEmpty())
                <x-ui.empty-state icon="calendar" title="Semana despejada" description="No hay tareas que venzan en los próximos días." class="py-8" />
            @else
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($this->upcomingTasks as $day => $tasks)
                        @php($date = \Illuminate\Support\Carbon::parse($day))
                        <div wire:key="day-{{ $day }}" class="min-w-0">
                            <p class="mb-1.5 flex items-baseline justify-between text-xs font-medium text-ink-muted">
                                <span class="first-letter:uppercase">{{ $date->isTomorrow() ? 'Mañana' : $date->translatedFormat('l j \\d\\e F') }}</span>
                                <span class="tabular-nums text-ink-subtle">{{ $tasks->count() }}</span>
                            </p>
                            <ul class="divide-y divide-line rounded-lg border border-line bg-surface">
                                @foreach ($tasks as $task)
                                    @include('livewire.dashboard.partials.task-row')
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.section>
    </div>
    @endif
</x-ui.page>
