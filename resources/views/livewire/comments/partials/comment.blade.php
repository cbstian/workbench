{{--
    One comment (root or reply). Variables: $comment, $isReply.
--}}
<article id="comment-{{ $comment->uuid }}" wire:key="comment-{{ $comment->id }}"
         x-data
         x-init="if (location.hash === '#comment-{{ $comment->uuid }}') { $nextTick(() => $el.scrollIntoView({ block: 'center' })); $el.classList.add('bg-hover'); setTimeout(() => $el.classList.remove('bg-hover'), 2500) }"
         class="-mx-2 flex scroll-mt-20 gap-3 rounded-lg px-2 py-1 transition-colors duration-700">
    @if ($comment->trashed())
        <span class="inline-flex {{ $isReply ? 'size-6' : 'size-7' }} shrink-0 items-center justify-center rounded-md border border-dashed border-line-strong"></span>
        <p class="py-1 text-sm text-ink-subtle italic">Comentario eliminado</p>
    @else
        <x-app.avatar :name="$comment->user?->name ?? '?'" :class="$isReply ? 'shrink-0 size-6!' : 'shrink-0'" />
        <div class="min-w-0 flex-1">
            <header class="flex flex-wrap items-baseline gap-x-2 text-sm">
                <span class="font-medium text-ink">{{ $comment->user?->name ?? '—' }}</span>
                <a href="#comment-{{ $comment->uuid }}" class="text-xs text-ink-subtle hover:text-ink" title="{{ $comment->created_at->translatedFormat('j M Y, H:i') }}">
                    <time datetime="{{ $comment->created_at->toIso8601String() }}">{{ $comment->created_at->diffForHumans() }}</time>
                </a>
                @if ($comment->edited_at)
                    <span class="text-xs text-ink-subtle">· editado</span>
                @endif

                @if ($editingId !== $comment->id)
                    <span class="ml-auto flex gap-2">
                        @if ($this->canComment)
                            <button type="button" wire:click="reply({{ $comment->id }})" class="text-xs text-ink-subtle hover:text-ink">Responder</button>
                        @endif
                        @if ($this->canManage($comment))
                            @if ($comment->user_id === auth()->id())
                                <button type="button" wire:click="edit({{ $comment->id }})" class="text-xs text-ink-subtle hover:text-ink">Editar</button>
                            @endif
                            <button type="button" wire:click="delete({{ $comment->id }})" wire:confirm="¿Enviar este comentario a la papelera?" class="text-xs text-ink-subtle hover:text-ink">Eliminar</button>
                        @endif
                    </span>
                @endif
            </header>

            @if ($editingId === $comment->id)
                <div class="mt-1">
                    @include('livewire.comments.partials.composer', [
                        'model' => 'editingBody',
                        'submit' => 'saveEdit',
                        'submitText' => 'Guardar',
                        'placeholder' => '',
                        'cancel' => 'cancelEdit',
                        'autofocus' => true,
                    ])
                </div>
            @else
                <p class="mt-0.5 text-sm break-words whitespace-pre-line text-ink">{{ $this->bodyHtml($comment) }}</p>
            @endif
        </div>
    @endif
</article>
