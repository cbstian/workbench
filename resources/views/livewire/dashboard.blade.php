<div class="space-y-10">
    <x-ui.page-header :title="$this->greeting" :description="now()->translatedFormat('l j \\d\\e F')" />

    <section class="grid gap-3 sm:grid-cols-3" aria-label="Resumen">
        @foreach ($this->stats as $stat)
            <x-ui.stat :label="$stat['label']" :value="$stat['value']" :hint="$stat['hint']" />
        @endforeach
    </section>

    <section class="space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-medium text-ink-muted">Actividad reciente</h2>
            <a href="{{ route('projects.index') }}" wire:navigate class="text-sm text-ink-muted underline-offset-4 hover:text-ink hover:underline">
                Ver todos
            </a>
        </div>

        <ul class="divide-y divide-line rounded-lg border border-line">
            @forelse ($this->recentProjects as $project)
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
            @empty
                <li class="px-4 py-10 text-center text-sm text-ink-muted">Aún no hay proyectos.</li>
            @endforelse
        </ul>
    </section>
</div>
