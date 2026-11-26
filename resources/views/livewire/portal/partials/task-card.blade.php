{{--
    A task card of the portal kanban. Variables: $task (with counts and relations).
--}}
<li wire:key="portal-card-{{ $task->id }}">
    <button type="button" wire:click="openTask('{{ $task->uuid }}')"
            class="block w-full rounded-lg border border-line bg-surface p-3 text-left shadow-xs transition hover:border-line-strong hover:shadow-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none">
        <span @class(['block text-sm', 'text-ink-muted line-through' => $task->completed_at, 'font-medium text-ink' => ! $task->completed_at])>{{ $task->name }}</span>

        @if ($task->labels->isNotEmpty())
            <span class="mt-2 flex flex-wrap gap-1">
                @foreach ($task->labels as $label)
                    <x-app.color-tag :color="$label->color" :text="$label->name" />
                @endforeach
            </span>
        @endif

        <span class="mt-2.5 flex items-center gap-2 text-xs text-ink-subtle">
            <x-app.color-tag :color="$task->priority->color" :text="$task->priority->name" />
            @if ($task->client_comments_count > 0)
                <span class="flex items-center gap-1 tabular-nums" title="Mensajes en la conversación">
                    <x-icon icon="chat-bubble-left" class="size-3.5" />
                    {{ $task->client_comments_count }}
                </span>
            @endif
            @include('livewire.projects.partials.task-meta', ['task' => $task])
        </span>
    </button>
</li>
