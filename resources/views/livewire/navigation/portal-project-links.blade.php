<div class="space-y-0.5">
    @forelse ($this->projects as $project)
        <x-app.nav-item :href="route('portal.projects.show', $project)"
                        :emoji="$project->icon ?? '📁'"
                        :active="$activeProjectId === $project->id"
                        wire:key="nav-portal-project-{{ $project->id }}">
            {{ $project->name }}
        </x-app.nav-item>
    @empty
        <p class="px-2 py-1 text-xs text-ink-subtle">Sin proyectos compartidos</p>
    @endforelse
</div>
