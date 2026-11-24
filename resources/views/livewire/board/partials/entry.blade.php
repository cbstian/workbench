{{--
    One board note. On a project row, notes linked to a task hide while the
    sub-rows are open (they are shown on the task row instead).

    Expects $entry, $choices, and $onProjectRow.
--}}
@php($linked = $entry->task_id !== null)
<div class="relative" @if ($onProjectRow && $linked) x-show="!open" x-cloak @endif wire:key="entry-{{ $entry->id }}">
    @if ($this->canEditNotes)
        <div x-data="{ menu: false, body: @js($entry->body), task: @js($entry->task?->uuid ?? '') }" @click.outside="menu = false">
            <button type="button" @click="menu = !menu"
                    data-entry="{{ $entry->body }}" data-overdue="{{ $entry->isOverdue() ? '1' : '0' }}"
                    title="{{ $entry->creator?->name }}"
                    @class([
                        'flex w-full items-start gap-1 rounded border px-1 py-0.5 text-left leading-snug',
                        'border-overdue bg-surface text-overdue' => $entry->isOverdue(),
                        'border-line bg-surface text-ink-muted' => $entry->status === \App\Enums\BoardEntryStatus::Done,
                        'border-line bg-surface text-ink' => $entry->status === \App\Enums\BoardEntryStatus::Planned && ! $entry->isOverdue(),
                    ])>
                @if ($entry->status === \App\Enums\BoardEntryStatus::Done)
                    <x-icon icon="check" class="mt-0.5 size-3 shrink-0 text-priority-medium" />
                    <span class="sr-only">Hecha.</span>
                @elseif ($entry->isOverdue())
                    <span class="sr-only">Vencida.</span>
                @else
                    <span class="sr-only">Planificada.</span>
                @endif
                <span class="min-w-0 break-words">
                    {{ $entry->body }}
                    @if ($entry->task && $onProjectRow)
                        <span class="text-ink-subtle">· {{ $entry->task->name }}</span>
                    @endif
                </span>
            </button>

            <div x-show="menu" x-cloak class="absolute left-0 z-40 mt-1 w-56 rounded-md border border-line bg-elevated p-2 text-ink shadow-sm">
                <p class="mb-1 text-[10px] text-ink-subtle">{{ $entry->creator?->name }}</p>
                <input x-model="body" maxlength="80" @keydown.enter.prevent="$wire.updateEntry(@js($entry->uuid), body, task)"
                       class="w-full rounded border border-line bg-surface px-1.5 py-1 text-xs text-ink"
                       aria-label="Texto de la nota" />
                <select x-model="task" class="mt-1 w-full rounded border border-line bg-surface px-1 py-1 text-xs text-ink" aria-label="Tarea vinculada">
                    <option value="">Sin tarea</option>
                    @foreach ($choices as $choice)
                        <option value="{{ $choice['value'] }}">{{ $choice['label'] }}</option>
                    @endforeach
                    @if ($entry->task && collect($choices)->doesntContain('value', $entry->task->uuid))
                        <option value="{{ $entry->task->uuid }}">{{ $entry->task->name }}</option>
                    @endif
                </select>
                <div class="mt-1.5 flex flex-wrap gap-1">
                    <button type="button" class="rounded px-1.5 py-0.5 text-[11px] text-ink hover:bg-hover" wire:click="toggleDone('{{ $entry->uuid }}')">
                        {{ $entry->status === \App\Enums\BoardEntryStatus::Done ? 'Marcar planificada' : 'Marcar hecha' }}
                    </button>
                    <button type="button" class="rounded px-1.5 py-0.5 text-[11px] text-ink hover:bg-hover" @click="$wire.updateEntry(@js($entry->uuid), body, task)">
                        Guardar
                    </button>
                    <button type="button" class="rounded px-1.5 py-0.5 text-[11px] text-overdue hover:bg-hover" wire:click="confirmDelete('{{ $entry->uuid }}')">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    @else
        <div data-entry="{{ $entry->body }}" data-overdue="{{ $entry->isOverdue() ? '1' : '0' }}"
             title="{{ $entry->creator?->name }}"
             @class([
                 'flex w-full items-start gap-1 rounded border px-1 py-0.5 leading-snug',
                 'border-overdue bg-surface text-overdue' => $entry->isOverdue(),
                 'border-line bg-surface text-ink-muted' => $entry->status === \App\Enums\BoardEntryStatus::Done,
                 'border-line bg-surface text-ink' => $entry->status === \App\Enums\BoardEntryStatus::Planned && ! $entry->isOverdue(),
             ])>
            @if ($entry->status === \App\Enums\BoardEntryStatus::Done)
                <x-icon icon="check" class="mt-0.5 size-3 shrink-0 text-priority-medium" />
            @endif
            <span class="min-w-0 break-words">{{ $entry->body }}</span>
        </div>
    @endif
</div>
