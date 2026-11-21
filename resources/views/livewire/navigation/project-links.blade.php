<div class="space-y-0.5">
    @forelse ($this->projects->take(\App\Livewire\Navigation\ProjectLinks::LIMIT) as $project)
        <x-app.nav-item :href="route('projects.show', $project)"
                        :emoji="$project->icon ?? '📁'"
                        :active="$activeProjectId === $project->id"
                        wire:key="nav-project-{{ $project->id }}">
            {{ $project->name }}
        </x-app.nav-item>
    @empty
        <p class="px-2 py-1 text-xs text-ink-subtle">Sin proyectos</p>
    @endforelse

    @if ($this->projects->count() > \App\Livewire\Navigation\ProjectLinks::LIMIT)
        <a href="{{ route('projects.index') }}" wire:navigate class="flex h-7 items-center rounded-md px-2 text-xs text-ink-subtle transition-colors hover:bg-hover hover:text-ink">
            Ver todos
        </a>
    @endif
</div>
