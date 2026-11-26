<x-ui.page>
    <x-ui.page-header :title="$this->greeting" icon="👋"
                      :description="'Aquí sigues el avance de tus proyectos con '.$this->workspace->name.', lees su documentación y conversas con el equipo.'" />

    <div class="grid gap-10 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="min-w-0 space-y-10">
            @forelse ($this->clients as $client)
                <section wire:key="portal-client-{{ $client->id }}" class="space-y-4" aria-labelledby="client-{{ $client->id }}">
                    <header class="flex items-center gap-3">
                        <x-app.client-logo :client="$client" size="lg" />
                        <div class="min-w-0">
                            <h2 id="client-{{ $client->id }}" class="truncate text-lg font-semibold text-ink">{{ $client->name }}</h2>
                            <p class="truncate text-sm text-ink-muted">
                                {{ $client->description ?: trans_choice('{0} Sin proyectos compartidos todavía|{1} 1 proyecto|[2,*] :count proyectos', $client->projects->count()) }}
                            </p>
                        </div>
                    </header>

                    @if ($client->projects->isEmpty())
                        <x-ui.empty-state icon="folder" title="Aún no hay proyectos compartidos"
                                          description="Cuando el equipo comparta un proyecto contigo, lo verás aquí." />
                    @else
                        <div class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-3">
                            @foreach ($client->projects as $project)
                                @include('livewire.portal.partials.project-card', ['project' => $project, 'client' => $client])
                            @endforeach
                        </div>
                    @endif
                </section>
            @empty
                <x-ui.empty-state icon="folder" title="Aún no hay proyectos compartidos"
                                  description="Cuando el equipo comparta un proyecto contigo, lo verás aquí." />
            @endforelse
        </div>

        <aside class="min-w-0 space-y-8">
            <x-ui.section title="Próximas entregas" :description="'Tareas abiertas que terminan en los próximos '.\App\Livewire\Portal\Home::UPCOMING_DAYS.' días'">
                @if ($this->upcoming->isEmpty())
                    <p class="text-sm text-ink-muted">Nada por entregar en estos días.</p>
                @else
                    <ul class="divide-y divide-line overflow-hidden rounded-lg border border-line bg-surface">
                        @foreach ($this->upcoming as $task)
                            <li wire:key="upcoming-{{ $task->id }}">
                                <a href="{{ route('portal.projects.show', ['project' => $task->project, 'tarea' => $task->uuid]) }}" wire:navigate
                                   class="flex items-center gap-3 px-3 py-2.5 transition-colors hover:bg-hover">
                                    <span class="flex w-10 shrink-0 flex-col items-center rounded-md border border-line py-0.5 leading-tight">
                                        <span class="text-[10px] text-ink-subtle uppercase">{{ $task->ends_on->translatedFormat('M') }}</span>
                                        <span class="text-sm font-semibold text-ink tabular-nums">{{ $task->ends_on->day }}</span>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm text-ink">{{ $task->name }}</span>
                                        <span class="block truncate text-xs text-ink-muted">{{ $task->project->icon }} {{ $task->project->name }}</span>
                                    </span>
                                    @if ($task->ends_on->isToday())
                                        <span class="shrink-0 rounded-md bg-today px-1.5 py-0.5 text-[11px] font-medium text-ink">Hoy</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.section>

            <x-ui.section id="documentacion" title="Documentación" description="Páginas que el equipo comparte con todos sus clientes">
                @if ($this->documentation->isEmpty())
                    <p class="text-sm text-ink-muted">Aún no hay documentación compartida.</p>
                @else
                    @include('livewire.portal.partials.page-tree', ['pages' => $this->documentation, 'activeId' => null])
                @endif
            </x-ui.section>
        </aside>
    </div>
</x-ui.page>
