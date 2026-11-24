{{--
    A day cell. Click the empty area (or +) to write a planned note; check
    «Ya hecho» to log work that was not planned. Enter saves.

    Expects $project, $date, $entries, $idle, $taskUuid, $choices, $onProjectRow.
--}}
<td @class([
        'relative border-b border-r border-line p-1 align-top',
        'bg-today' => $date === today()->toDateString() && ! $project->isPaused(),
        'bg-canvas' => $date !== today()->toDateString() || $project->isPaused(),
    ])
    @if ($this->canEditNotes)
        x-data="{ adding: false, body: '', done: false }"
        @click="if (!adding && $event.target === $el) { adding = true; $nextTick(() => $refs.note.focus()) }"
    @endif
    data-date="{{ $date }}" data-idle="{{ $idle ? '1' : '0' }}">
    @if ($project->isPaused())
        <svg class="pointer-events-none absolute inset-0 h-full w-full text-ink-subtle" aria-hidden="true">
            <rect width="100%" height="100%" fill="url(#wb-board-wave)" />
        </svg>
    @endif

    @if ($idle)
        <span class="absolute top-1 right-1 z-[1] size-1 rounded-full bg-ink-subtle" title="Sin trabajo este día">
            <span class="sr-only">Sin trabajo este día</span>
        </span>
    @endif

    <div class="relative flex min-h-8 flex-col gap-1">
        @foreach ($entries as $entry)
            @include('livewire.board.partials.entry', ['entry' => $entry, 'choices' => $choices, 'onProjectRow' => $onProjectRow])
        @endforeach

        @if ($this->canEditNotes)
            <button type="button" x-show="!adding" @click.stop="adding = true; $nextTick(() => $refs.note.focus())"
                    class="rounded text-ink-subtle opacity-0 hover:bg-hover hover:text-ink focus:opacity-100 group-hover:opacity-100">
                <span class="sr-only">Agregar nota</span>
                <span aria-hidden="true">+</span>
            </button>
            <form x-show="adding" x-cloak class="space-y-1" @submit.prevent="
                $wire.addEntry(@js($project->uuid), @js($date), body, done, @js($taskUuid)).then((saved) => {
                    if (saved) { adding = false; body = ''; done = false }
                })
            " @click.stop>
                <input x-ref="note" x-model="body" maxlength="80" placeholder="Nota"
                       @keydown.escape="adding = false"
                       class="w-full rounded border border-line bg-surface px-1 py-0.5 text-[11px] text-ink placeholder:text-ink-subtle"
                       aria-label="Nueva nota" />
                <label class="flex items-center gap-1 text-[10px] text-ink-muted">
                    <input type="checkbox" x-model="done" class="size-3 rounded border-line text-accent" />
                    Ya hecho
                </label>
            </form>
        @endif
    </div>
</td>
