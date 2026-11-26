@php
    $isClientThread = $thread === \App\Enums\CommentThread::Client;
    $isClientUser = $this->readsFromPortal;
@endphp

<div>
    <x-ui.section :title="$bare ? null : match (true) { $isClientUser => 'Conversación con el equipo', $isClientThread => $thread->label(), default => 'Comentarios' }"
                  :description="$isClientThread && ! $isClientUser && ! $bare ? 'Lo que escribas aquí lo ve el cliente en su portal.' : null">
        <x-slot:actions>
            <button type="button" wire:click="toggleFollow" class="inline-flex items-center gap-1 text-xs text-ink-subtle hover:text-ink"
                    title="{{ $this->isFollowing ? 'Recibes avisos de los comentarios nuevos' : 'No recibes avisos, salvo cuando te mencionan o responden' }}">
                <x-icon :icon="$this->isFollowing ? 'bell-slash' : 'bell'" class="size-4" />
                {{ $this->isFollowing ? 'Dejar de seguir' : 'Seguir' }}
            </button>
        </x-slot:actions>

        <div class="space-y-4">
            @if ($this->threads->isEmpty() && ! $this->canComment)
                <p class="text-sm text-ink-muted">{{ $isClientThread ? 'Aún no hay mensajes.' : 'Sin comentarios.' }}</p>
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
                    'submitText' => $isClientThread && $isClientUser ? 'Enviar' : 'Comentar',
                    'placeholder' => match (true) {
                        $isClientThread && $isClientUser => 'Escribe una pregunta o un comentario para el equipo…',
                        $isClientThread => 'Escribe un mensaje para el cliente…',
                        default => 'Escribe un comentario…',
                    },
                ])
            @endif
        </div>
    </x-ui.section>
</div>
