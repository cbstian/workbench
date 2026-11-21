{{--
    Compact task details for cards: subtask progress, due date (marked when
    overdue) and assignee avatars. Expects $task with subtasks counts and assignees.
--}}
@if ($task->subtasks_count > 0)
    <span class="flex items-center gap-1 tabular-nums" title="Subtareas cerradas">
        <x-icon icon="list-bullet" class="size-3.5" />
        {{ $task->closed_subtasks_count }}/{{ $task->subtasks_count }}
    </span>
@endif

@if ($task->ends_on)
    @php($isOverdue = ! $task->completed_at && $task->ends_on->lt(today()))
    <time datetime="{{ $task->ends_on->toDateString() }}" @if ($isOverdue) title="Vencida" @endif
          @class(['flex items-center gap-1 tabular-nums', 'font-semibold text-ink' => $isOverdue])>
        @if ($isOverdue)
            <x-icon icon="exclamation-circle" class="size-3.5" />
        @endif
        {{ $task->ends_on->translatedFormat('j M') }}
    </time>
@endif

<span class="ml-auto flex -space-x-1.5">
    @foreach ($task->assignees->take(3) as $assignee)
        <x-app.avatar :name="$assignee->name" class="size-6 ring-2 ring-surface" />
    @endforeach
</span>
