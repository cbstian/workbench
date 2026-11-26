<x-ui.page>
    @php
        $project = $task->project;
        $breadcrumbs = [
            ['label' => 'Proyectos', 'link' => route('projects.index')],
            ['label' => $project->name, 'link' => route('projects.show', $project)],
        ];

        if ($task->parent) {
            $breadcrumbs[] = ['label' => $task->parent->name, 'link' => route('tasks.show', $task->parent)];
        }

        $breadcrumbs[] = ['label' => $task->name];
    @endphp

    <x-ui.page-header :title="$task->name" :breadcrumbs="$breadcrumbs"
                      :description="$task->parent ? 'Subtarea de «'.$task->parent->name.'»' : 'Creada por '.($task->creator?->name ?? '—').', '.$task->created_at->translatedFormat('j M Y')">
        <x-slot:actions>
            @if ($task->completed_at)
                <x-badge text="Cerrada" color="green" light round />
            @endif
            @can('delete', $task)
                <x-button icon="trash" text="Enviar a la papelera" sm flat color="secondary" wire:click="confirmDelete" />
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,22rem)]">
        <div class="min-w-0 space-y-8">
            <form wire:submit="save" class="space-y-5">
                @unless ($this->canEdit)
                    <div class="flex items-start gap-2 rounded-md border border-line bg-hover px-3 py-2 text-sm text-ink-muted">
                        <x-icon icon="lock-closed" class="mt-0.5 size-4 shrink-0" />
                        Puedes ver esta tarea, pero solo la editan los admins del proyecto y quienes la tienen asignada.
                    </div>
                @endunless

                @include('livewire.tasks.partials.fields', ['readonly' => ! $this->canEdit])

                @if ($this->canEdit)
                    <x-ui.form-actions>
                        <x-button type="submit" text="Guardar cambios" sm loading="save" />
                    </x-ui.form-actions>
                @endif
            </form>

            @if ($task->parent_id === null)
                <x-ui.section title="Subtareas" :description="$this->subtasks->isEmpty() ? 'Divide la tarea en pasos. Las subtareas tienen los mismos datos y un solo nivel.' : null">
                    @if ($this->subtasks->isNotEmpty())
                        <ul class="divide-y divide-line rounded-lg border border-line bg-surface">
                            @foreach ($this->subtasks as $subtask)
                                <li wire:key="subtask-{{ $subtask->id }}">
                                    <a href="{{ route('tasks.show', $subtask) }}" wire:navigate class="flex items-center gap-3 px-3 py-2 transition-colors hover:bg-hover">
                                        <x-icon :icon="$subtask->completed_at ? 'check-circle' : 'stop-circle'" class="size-4 shrink-0 text-ink-subtle" />
                                        <span @class(['min-w-0 flex-1 truncate text-sm', 'text-ink-muted line-through' => $subtask->completed_at, 'text-ink' => ! $subtask->completed_at])>{{ $subtask->name }}</span>
                                        <x-app.color-tag :color="$subtask->status->color" :text="$subtask->status->name" class="shrink-0" />
                                        <span class="flex shrink-0 -space-x-1.5">
                                            @foreach ($subtask->assignees->take(3) as $assignee)
                                                <x-app.avatar :name="$assignee->name" class="size-6 ring-2 ring-surface" />
                                            @endforeach
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($this->canAddSubtasks)
                        <form wire:submit="addSubtask" class="flex items-start gap-2">
                            <div class="min-w-0 flex-1">
                                <x-input wire:model="newSubtaskName" placeholder="Nueva subtarea…" maxlength="255" />
                            </div>
                            <x-button type="submit" icon="plus" text="Agregar" sm loading="addSubtask" />
                        </form>
                    @endif
                </x-ui.section>
            @endif

            <livewire:tasks.attachments :task="$task" wire:key="attachments-{{ $task->id }}" />
            <livewire:comments.thread :commentable="$task" wire:key="comments-{{ $task->id }}" />
        </div>

        <aside class="min-w-0">
            <x-ui.section title="Historial">
                @if ($this->activities->isEmpty())
                    <p class="text-sm text-ink-muted">Sin cambios registrados.</p>
                @else
                    <ol class="space-y-3 border-l border-line pl-4">
                        @foreach ($this->activities as $activity)
                            <li wire:key="activity-{{ $activity->id }}" class="relative text-sm">
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
            </x-ui.section>
        </aside>
    </div>
</x-ui.page>
