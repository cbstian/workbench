<x-ui.page>
    <x-ui.page-header :title="$this->greeting" :description="now()->translatedFormat('l j \\d\\e F')" />

    <section class="grid gap-3 sm:grid-cols-3" aria-label="Resumen">
        @foreach ($this->stats as $stat)
            <x-ui.stat :label="$stat['label']" :value="$stat['value']" :hint="$stat['hint']" />
        @endforeach
    </section>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-ui.section title="Proyectos creados" description="Últimos seis meses" class="lg:col-span-2">
            <x-card>
                <x-ui.chart :options="$this->projectsPerMonthChart" height="260" />
            </x-card>
        </x-ui.section>

        <x-ui.section title="Visibilidad para el cliente" description="Todos los proyectos">
            <x-card>
                <x-ui.chart :options="$this->visibilityChart" height="260" />
            </x-card>
        </x-ui.section>
    </div>

    <x-ui.section title="Actividad reciente">
        <x-slot:actions>
            <a href="{{ route('projects.index') }}" wire:navigate class="text-ink-muted underline-offset-4 hover:text-ink hover:underline">
                Ver todos
            </a>
        </x-slot:actions>

        @if ($this->recentProjects->isEmpty())
            <x-ui.empty-state icon="folder" title="Aún no hay proyectos" />
        @else
            <ul class="divide-y divide-line rounded-lg border border-line">
                @foreach ($this->recentProjects as $project)
                    <li wire:key="recent-{{ $project->id }}" class="flex items-center gap-3 px-4 py-3 transition-colors hover:bg-hover">
                        <span class="text-lg leading-none" aria-hidden="true">{{ $project->icon }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-ink">{{ $project->name }}</p>
                            @if ($project->description)
                                <p class="truncate text-xs text-ink-muted">{{ $project->description }}</p>
                            @endif
                        </div>
                        <time datetime="{{ $project->updated_at->toIso8601String() }}" class="shrink-0 text-xs text-ink-subtle">
                            {{ $project->updated_at->diffForHumans() }}
                        </time>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.section>
</x-ui.page>
