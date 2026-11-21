<div>
    <x-ui.section title="Comentarios">
        <div class="space-y-4">
            @if ($this->comments->isEmpty() && ! $this->canComment)
                <p class="text-sm text-ink-muted">Sin comentarios.</p>
            @endif

            @foreach ($this->comments as $comment)
                <article wire:key="comment-{{ $comment->id }}" class="flex gap-3">
                    <x-app.avatar :name="$comment->user?->name ?? '?'" class="shrink-0" />
                    <div class="min-w-0 flex-1">
                        <header class="flex flex-wrap items-baseline gap-x-2 text-sm">
                            <span class="font-medium text-ink">{{ $comment->user?->name ?? '—' }}</span>
                            <time datetime="{{ $comment->created_at->toIso8601String() }}" class="text-xs text-ink-subtle" title="{{ $comment->created_at->translatedFormat('j M Y, H:i') }}">
                                {{ $comment->created_at->diffForHumans() }}
                            </time>
                            @if ($comment->edited_at)
                                <span class="text-xs text-ink-subtle">· editado</span>
                            @endif

                            @if ($editingId !== $comment->id && $this->canManage($comment))
                                <span class="ml-auto flex gap-1">
                                    @if ($comment->user_id === auth()->id())
                                        <button type="button" wire:click="edit({{ $comment->id }})" class="text-xs text-ink-subtle hover:text-ink">Editar</button>
                                    @endif
                                    <button type="button" wire:click="delete({{ $comment->id }})" wire:confirm="¿Enviar este comentario a la papelera?" class="text-xs text-ink-subtle hover:text-ink">Eliminar</button>
                                </span>
                            @endif
                        </header>

                        @if ($editingId === $comment->id)
                            <form wire:submit="saveEdit" class="mt-1 space-y-2">
                                <x-textarea wire:model="editingBody" rows="3" />
                                <x-ui.form-actions>
                                    <x-button flat color="secondary" text="Cancelar" sm wire:click="cancelEdit" />
                                    <x-button type="submit" text="Guardar" sm loading="saveEdit" />
                                </x-ui.form-actions>
                            </form>
                        @else
                            <p class="mt-0.5 text-sm break-words whitespace-pre-line text-ink">{{ $comment->body }}</p>
                        @endif
                    </div>
                </article>
            @endforeach

            @if ($this->canComment)
                <form wire:submit="add" class="space-y-2">
                    <x-textarea wire:model="body" rows="3" placeholder="Escribe un comentario…" />
                    <x-ui.form-actions>
                        <x-button type="submit" text="Comentar" sm loading="add" />
                    </x-ui.form-actions>
                </form>
            @endif
        </div>
    </x-ui.section>
</div>
