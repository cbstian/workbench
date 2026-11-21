<x-ui.page>
    <x-ui.page-header :title="$project->name"
                      :icon="$project->icon"
                      :description="$project->description"
                      :breadcrumbs="[
                          ['label' => 'Proyectos', 'link' => route('projects.index')],
                          ['label' => $project->name],
                      ]">
        <x-slot:actions>
            @if ($project->client)
                <span class="flex items-center gap-2 text-sm text-ink-muted">
                    <x-app.client-logo :client="$project->client" size="sm" />
                    {{ $project->client->name }}
                </span>
            @endif

            @if ($project->is_visible_to_client)
                <x-badge text="Visible para el cliente" color="primary" light round />
            @else
                <x-badge text="Oculto para el cliente" color="secondary" light round />
            @endif

            @if ($project->members->isNotEmpty())
                <span class="flex -space-x-1.5" title="Equipo: {{ $project->members->pluck('name')->join(', ') }}">
                    @foreach ($project->members->take(5) as $member)
                        <x-app.avatar :name="$member->name" class="ring-2 ring-canvas" />
                    @endforeach
                    @if ($project->members->count() > 5)
                        <span class="inline-flex size-7 items-center justify-center rounded-md bg-hover text-xs text-ink-muted ring-2 ring-canvas">+{{ $project->members->count() - 5 }}</span>
                    @endif
                </span>
            @endif

            @can('viewProjectWiki', [\App\Models\Page::class, $project])
                <x-button icon="book-open" text="Wiki" sm outline :href="route('projects.wiki', $project)" wire:navigate />
            @endcan

            @can('configure', $project)
                <x-button icon="cog-6-tooth" text="Configurar" sm outline :href="route('projects.settings', $project)" wire:navigate />
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <livewire:projects.task-board :project="$project" wire:key="task-board-{{ $project->id }}" />
</x-ui.page>
