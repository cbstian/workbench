{{--
    One open task on the home page: priority dot, name, project, assignees,
    due label and a "complete" button when the user may edit it.

    Expects: $task (with project, priority, assignees, parent and subtask counts).
--}}
@php
    $isOverdue = $task->ends_on?->lt(today());
    $dueLabel = match (true) {
        $isOverdue => 'Venció hace '.trans_choice('{1} :count día|[2,*] :count días', (int) $task->ends_on->diffInDays(today())),
        (bool) $task->ends_on?->isToday() => 'Vence hoy',
        (bool) $task->starts_on?->isToday() => 'Empieza hoy',
        $task->ends_on !== null => 'Vence '.$task->ends_on->translatedFormat('D j M'),
        default => null,
    };
@endphp

<li wire:key="task-{{ $task->id }}" class="group flex items-center gap-3 px-4 py-2.5 transition-colors hover:bg-hover">
    <span class="size-2.5 shrink-0 rounded-full" style="background-color: {{ $task->priority?->color ?? 'var(--wb-priority-none)' }}"
          title="Prioridad {{ $task->priority?->name ?? 'ninguna' }}"></span>

    <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2">
            <a href="{{ route('tasks.show', $task) }}" wire:navigate class="truncate text-sm font-medium text-ink hover:underline">{{ $task->name }}</a>
            @if ($this->isHighPriority($task))
                <span class="shrink-0 rounded px-1.5 py-0.5 text-[11px] font-medium text-overdue ring-1 ring-overdue/40 first-letter:uppercase">{{ $task->priority->name }}</span>
            @endif
        </div>
        <p class="truncate text-xs text-ink-muted">
            <a href="{{ route('projects.show', $task->project) }}" wire:navigate class="hover:text-ink hover:underline">{{ $task->project->icon }} {{ $task->project->name }}</a>
            @if ($task->parent)
                · en <a href="{{ route('tasks.show', $task->parent) }}" wire:navigate class="hover:text-ink hover:underline">{{ $task->parent->name }}</a>
            @endif
            @if ($task->subtasks_count > 0)
                · {{ $task->closed_subtasks_count }}/{{ $task->subtasks_count }} subtareas
            @endif
        </p>
    </div>

    @if ($dueLabel)
        <span @class(['hidden shrink-0 text-xs tabular-nums sm:inline', 'font-semibold text-overdue' => $isOverdue, 'text-ink-muted' => ! $isOverdue])>{{ $dueLabel }}</span>
    @endif

    <span class="flex shrink-0 -space-x-1.5">
        @forelse ($task->assignees->take(3) as $assignee)
            <x-app.avatar :name="$assignee->name" class="size-6 ring-2 ring-surface" />
        @empty
            <span class="text-xs text-ink-subtle">Sin asignar</span>
        @endforelse
    </span>

    @can('update', $task)
        <x-button.circle icon="check" xs flat color="secondary" wire:click="complete({{ $task->id }})"
                         wire:loading.attr="disabled" title="Marcar como completada" aria-label="Marcar «{{ $task->name }}» como completada" />
    @endcan
</li>
