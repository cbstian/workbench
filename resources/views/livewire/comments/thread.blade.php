<div>
    <x-ui.section title="Comentarios">
        <x-slot:actions>
            <button type="button" wire:click="toggleFollow" class="inline-flex items-center gap-1 text-xs text-ink-subtle hover:text-ink"
                    title="{{ $this->isFollowing ? 'Recibes avisos de los comentarios nuevos' : 'No recibes avisos, salvo cuando te mencionan o responden' }}">
                <x-icon :icon="$this->isFollowing ? 'bell-slash' : 'bell'" class="size-4" />
                {{ $this->isFollowing ? 'Dejar de seguir' : 'Seguir' }}
            </button>
        </x-slot:actions>

        <div class="space-y-4">
            @if ($this->threads->isEmpty() && ! $this->canComment)
                <p class="text-sm text-ink-muted">Sin comentarios.</p>
            @endif

            @foreach ($this->threads as $root)
                <div wire:key="thread-{{ $root->id }}" class="space-y-2">
                    @include('livewire.comments.partials.comment', ['comment' => $root, 'isReply' => false])

                    @if ($root->replies->isNotEmpty() || $replyingToId === $root->id)
                        <div class="ml-5 space-y-2 border-l border-line pl-4">
                            @foreach ($root->replies as $reply)
                                @include('livewire.comments.partials.comment', ['comment' => $reply, 'isReply' => true])
                            @endforeach

                            @if ($replyingToId === $root->id)
                                <div wire:key="reply-form-{{ $root->id }}">
                                    @include('livewire.comments.partials.composer', [
                                        'model' => 'replyBody',
                                        'submit' => 'addReply',
                                        'submitText' => 'Responder',
                                        'placeholder' => 'Escribe una respuesta…',
                                        'cancel' => 'cancelReply',
                                        'autofocus' => true,
                                    ])
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach

            @if ($this->canComment)
                @include('livewire.comments.partials.composer', [
                    'model' => 'body',
                    'submit' => 'add',
                    'submitText' => 'Comentar',
                    'placeholder' => 'Escribe un comentario…',
                ])
            @endif
        </div>
    </x-ui.section>
</div>
