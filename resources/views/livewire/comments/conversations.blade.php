<div>
    @if ($this->showsClientThread)
        @php
            $tabs = [
                \App\Enums\CommentThread::Internal->value => ['label' => 'Internos', 'icon' => 'lock-closed'],
                \App\Enums\CommentThread::Client->value => ['label' => 'Con el cliente', 'icon' => 'chat-bubble-left-right'],
            ];
        @endphp

        <x-ui.section title="Comentarios"
                      x-data="{ tab: new URLSearchParams(location.search).get('hilo') === 'cliente' ? 'client' : 'internal' }">
            <div class="inline-flex items-center gap-1 rounded-lg bg-hover p-1" role="tablist" aria-label="Conversaciones">
                @foreach ($tabs as $value => $tab)
                    <button type="button" role="tab" x-on:click="tab = '{{ $value }}'"
                            :aria-selected="tab === '{{ $value }}'"
                            :class="tab === '{{ $value }}' ? 'bg-surface font-medium text-ink shadow-xs' : 'text-ink-muted hover:text-ink'"
                            class="inline-flex h-7 items-center gap-1.5 rounded-md px-2.5 text-sm transition-colors">
                        <x-icon :icon="$tab['icon']" class="size-4" />
                        {{ $tab['label'] }}
                        @if ($this->counts[$value] > 0)
                            <span class="text-xs text-ink-subtle tabular-nums">{{ $this->counts[$value] }}</span>
                        @endif
                    </button>
                @endforeach
            </div>

            <div x-show="tab === 'internal'" role="tabpanel">
                <livewire:comments.thread :commentable="$commentable" :thread="\App\Enums\CommentThread::Internal" bare
                                          wire:key="thread-internal-{{ $commentable->commentableType() }}-{{ $commentable->id }}" />
            </div>
            <div x-show="tab === 'client'" x-cloak role="tabpanel" class="space-y-3">
                <p class="flex items-start gap-2 rounded-md border border-line bg-hover px-3 py-2 text-xs text-ink-muted">
                    <x-icon icon="eye" class="mt-px size-4 shrink-0" />
                    El cliente ve estos mensajes en su portal y puede responder. Los comentarios internos nunca se le muestran.
                </p>
                <livewire:comments.thread :commentable="$commentable" :thread="\App\Enums\CommentThread::Client" bare
                                          wire:key="thread-client-{{ $commentable->commentableType() }}-{{ $commentable->id }}" />
            </div>
        </x-ui.section>
    @else
        <livewire:comments.thread :commentable="$commentable" wire:key="thread-internal-{{ $commentable->commentableType() }}-{{ $commentable->id }}" />
    @endif
</div>
