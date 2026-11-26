<div class="space-y-6">
    <header class="space-y-2">
        @if ($task->parent)
            <button type="button" wire:click="$dispatch('portal-open-task', { uuid: '{{ $task->parent->uuid }}' })"
                    class="flex items-center gap-1 text-xs text-ink-muted hover:text-ink">
                <x-icon icon="arrow-left" class="size-3.5" />
                Subtarea de «{{ $task->parent->name }}»
            </button>
        @endif
        <h2 @class(['text-xl font-semibold', 'text-ink-muted line-through' => $task->completed_at, 'text-ink' => ! $task->completed_at])>{{ $task->name }}</h2>
        <div class="flex flex-wrap items-center gap-2">
            <x-app.color-tag :color="$task->status->color" :text="$task->status->name" />
            <x-app.color-tag :color="$task->priority->color" :text="$task->priority->name" />
            @foreach ($task->labels as $label)
                <x-app.color-tag :color="$label->color" :text="$label->name" />
            @endforeach
            @if ($task->completed_at)
                <x-badge :text="'Cerrada el '.$task->completed_at->translatedFormat('j M')" color="green" light round />
            @endif
        </div>
    </header>

    <dl class="grid gap-4 rounded-xl border border-line bg-surface-muted p-4 text-sm sm:grid-cols-3">
        <div>
            <dt class="text-xs text-ink-muted">Inicio</dt>
            <dd class="mt-0.5 text-ink">{{ $task->starts_on?->translatedFormat('j M Y') ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs text-ink-muted">Término</dt>
            @php($isOverdue = ! $task->completed_at && $task->ends_on?->lt(today()))
            <dd @class(['mt-0.5', 'font-semibold text-overdue' => $isOverdue, 'text-ink' => ! $isOverdue])>
                {{ $task->ends_on?->translatedFormat('j M Y') ?? '—' }}
                @if ($isOverdue)
                    <span class="text-xs font-normal">· vencida</span>
                @endif
            </dd>
        </div>
        <div>
            <dt class="text-xs text-ink-muted">Responsables</dt>
            <dd class="mt-1">
                @if ($task->assignees->isEmpty())
                    <span class="text-ink-subtle">Sin asignar</span>
                @else
                    <ul class="space-y-1">
                        @foreach ($task->assignees as $assignee)
                            <li wire:key="portal-assignee-{{ $assignee->id }}" class="flex items-center gap-2 text-ink">
                                <x-app.avatar :name="$assignee->name" class="size-6" />
                                <span class="truncate">{{ $assignee->name }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </dd>
        </div>
    </dl>

    @if (filled($task->description))
        <x-ui.section title="Descripción">
            <article class="wb-prose">{!! $task->descriptionHtml() !!}</article>
        </x-ui.section>
    @endif

    @if ($this->subtasks->isNotEmpty())
        <x-ui.section title="Subtareas" :description="$this->subtasks->whereNotNull('completed_at')->count().' de '.$this->subtasks->count().' cerradas'">
            <ul class="divide-y divide-line rounded-lg border border-line bg-surface">
                @foreach ($this->subtasks as $subtask)
                    <li wire:key="portal-subtask-{{ $subtask->id }}">
                        <button type="button" wire:click="$dispatch('portal-open-task', { uuid: '{{ $subtask->uuid }}' })"
                                class="flex w-full items-center gap-3 px-3 py-2 text-left transition-colors hover:bg-hover">
                            <x-icon :icon="$subtask->completed_at ? 'check-circle' : 'stop-circle'" class="size-4 shrink-0 text-ink-subtle" />
                            <span @class(['min-w-0 flex-1 truncate text-sm', 'text-ink-muted line-through' => $subtask->completed_at, 'text-ink' => ! $subtask->completed_at])>{{ $subtask->name }}</span>
                            <x-app.color-tag :color="$subtask->status->color" :text="$subtask->status->name" class="shrink-0" />
                            <span class="flex shrink-0 -space-x-1.5">
                                @foreach ($subtask->assignees->take(3) as $assignee)
                                    <x-app.avatar :name="$assignee->name" class="size-6 ring-2 ring-surface" />
                                @endforeach
                            </span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </x-ui.section>
    @endif

    <div x-data="{ tab: 'conversation' }" class="space-y-4">
        <div class="inline-flex items-center gap-1 rounded-lg bg-hover p-1" role="tablist" aria-label="Conversación e historial">
            @foreach (['conversation' => ['Conversación', 'chat-bubble-left-right'], 'history' => ['Historial', 'clock']] as $value => [$label, $icon])
                <button type="button" role="tab" x-on:click="tab = '{{ $value }}'" :aria-selected="tab === '{{ $value }}'"
                        :class="tab === '{{ $value }}' ? 'bg-surface font-medium text-ink shadow-xs' : 'text-ink-muted hover:text-ink'"
                        class="inline-flex h-7 items-center gap-1.5 rounded-md px-2.5 text-sm transition-colors">
                    <x-icon :icon="$icon" class="size-4" />
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div x-show="tab === 'conversation'" role="tabpanel">
            <livewire:comments.thread :commentable="$task" :thread="\App\Enums\CommentThread::Client" bare
                                      wire:key="portal-thread-{{ $task->id }}" />
        </div>

        <div x-show="tab === 'history'" x-cloak role="tabpanel">
            @if ($this->activities->isEmpty())
                <p class="text-sm text-ink-muted">Sin cambios registrados.</p>
            @else
                <ol class="space-y-3 border-l border-line pl-4">
                    @foreach ($this->activities as $activity)
                        <li wire:key="portal-activity-{{ $activity->id }}" class="relative text-sm">
                            <span class="absolute top-1.5 -left-[21px] size-2 rounded-full border border-line-strong bg-surface" aria-hidden="true"></span>
                            <p class="text-ink">
                                <span class="font-medium">{{ $activity->user?->name ?? 'Sistema' }}</span>
                                <span class="text-ink-muted">{{ $activity->description() }}</span>
                            </p>
                            <time datetime="{{ $activity->created_at->toIso8601String() }}" class="text-xs text-ink-subtle" title="{{ $activity->created_at->translatedFormat('j M Y, H:i') }}">
                                {{ $activity->created_at->diffForHumans() }}
                            </time>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </div>
</div>
