<x-ui.page>
    <x-ui.page-header title="Bandeja" description="Menciones, respuestas a tus comentarios y comentarios nuevos en tareas y páginas que sigues.">
        <x-slot:actions>
            @if ($this->unreadCount > 0)
                <x-button text="Marcar todo como leído" icon="check" sm flat wire:click="markAllAsRead" loading="markAllAsRead" />
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="flex flex-wrap items-center gap-3">
        <div class="inline-flex rounded-md border border-line bg-surface p-0.5 text-sm" role="group" aria-label="Estado">
            @foreach (['unread' => 'Sin leer', 'all' => 'Todos'] as $value => $label)
                <button type="button" wire:click="$set('status', '{{ $value }}')"
                        @class([
                            'rounded px-3 py-1 transition-colors',
                            'bg-hover font-medium text-ink' => $status === $value,
                            'text-ink-muted hover:text-ink' => $status !== $value,
                        ])
                        aria-pressed="{{ $status === $value ? 'true' : 'false' }}">
                    {{ $label }}
                    @if ($value === 'unread' && $this->unreadCount > 0)
                        <span class="ml-1 text-xs text-ink-subtle">{{ $this->unreadCount }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        <div class="w-full max-w-48">
            <x-select.styled wire:model.live="kind" :options="$this->kindOptions" placeholder="Todos los tipos" />
        </div>
    </div>

    @if ($this->notifications->isEmpty())
        <x-ui.empty-state icon="bell"
                          :title="$status === 'unread' ? 'Estás al día' : 'Aún no tienes avisos'"
                          description="Te avisaremos cuando alguien te mencione, responda a tus comentarios o comente algo que sigues." />
    @else
        <div class="divide-y divide-line rounded-lg border border-line bg-surface">
            @foreach ($this->notifications as $notification)
                <div wire:key="notification-{{ $notification->id }}" class="flex items-center gap-2 px-2 py-1">
                    @include('livewire.notifications.partials.item', ['notification' => $notification, 'compact' => false])

                    @if ($notification->read_at === null)
                        <x-button.circle icon="check" flat sm color="secondary" wire:click="markAsRead('{{ $notification->id }}')" title="Marcar como leído" />
                    @else
                        <x-button.circle icon="envelope" flat sm color="secondary" wire:click="markAsUnread('{{ $notification->id }}')" title="Marcar como no leído" />
                    @endif
                </div>
            @endforeach
        </div>

        {{ $this->notifications->links() }}
    @endif
</x-ui.page>
