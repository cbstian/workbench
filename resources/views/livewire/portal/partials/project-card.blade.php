{{--
    A project in the portal home. Variables: $project (with tasks_* counts), $client.
--}}
<a href="{{ route('portal.projects.show', $project) }}" wire:navigate wire:key="portal-project-{{ $project->id }}"
   class="group relative flex flex-col gap-4 overflow-hidden rounded-xl border border-line bg-surface p-5 shadow-xs transition hover:-translate-y-0.5 hover:border-line-strong hover:shadow-md">
    <span class="absolute inset-x-0 top-0 h-1 bg-accent" @if ($client->color) style="background-color: {{ $client->color }}" @endif aria-hidden="true"></span>

    <div class="flex items-start gap-3">
        <span class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-hover text-2xl" aria-hidden="true">{{ $project->icon ?? '📁' }}</span>
        <div class="min-w-0 flex-1">
            <h3 class="truncate text-base font-semibold text-ink group-hover:underline">{{ $project->name }}</h3>
            @if ($project->description)
                <p class="mt-0.5 line-clamp-2 text-sm text-ink-muted">{{ $project->description }}</p>
            @endif
        </div>
        <x-icon icon="arrow-up-right" class="size-4 shrink-0 text-ink-subtle opacity-0 transition group-hover:opacity-100" />
    </div>

    @include('livewire.portal.partials.progress', ['closed' => $project->tasks_closed, 'total' => $project->tasks_total])

    <div class="mt-auto flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-ink-muted">
        <span class="flex items-center gap-1">
            <x-icon icon="rectangle-stack" class="size-3.5" />
            {{ $project->tasks_total - $project->tasks_closed }} {{ $project->tasks_total - $project->tasks_closed === 1 ? 'abierta' : 'abiertas' }}
        </span>
        @if ($project->tasks_overdue > 0)
            <span class="flex items-center gap-1 font-medium text-overdue">
                <x-icon icon="exclamation-circle" class="size-3.5" />
                {{ $project->tasks_overdue }} {{ $project->tasks_overdue === 1 ? 'vencida' : 'vencidas' }}
            </span>
        @endif
        @if ($project->due_on)
            <span class="flex items-center gap-1" title="Fecha de término">
                <x-icon icon="flag" class="size-3.5" />
                {{ $project->due_on->translatedFormat('j M Y') }}
            </span>
        @endif
        @if ($project->paused_at)
            <span class="ml-auto rounded-md bg-hover px-1.5 py-0.5 font-medium text-ink-muted">En pausa</span>
        @endif
    </div>
</a>
