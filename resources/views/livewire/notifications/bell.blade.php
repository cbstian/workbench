<div class="relative" wire:poll.30s.visible x-data="{ open: false }" x-on:keydown.escape.window="open = false" x-on:click.outside="open = false">
    <button type="button"
            x-on:click="open = !open"
            :aria-expanded="open"
            class="relative inline-flex size-8 items-center justify-center rounded-md text-ink-muted transition-colors hover:bg-hover hover:text-ink"
            aria-label="Avisos{{ $this->unreadCount > 0 ? ': '.$this->unreadCount.' sin leer' : '' }}"
            title="Avisos">
        <x-icon icon="bell" class="size-5" />
        @if ($this->unreadCount > 0)
            <span class="absolute top-0.5 right-0.5 inline-flex min-w-4 items-center justify-center rounded-full bg-accent px-1 text-[10px] leading-4 font-semibold text-canvas">
                {{ $this->unreadCount > 99 ? '99+' : $this->unreadCount }}
            </span>
        @endif
    </button>

    <div x-cloak x-show="open" x-transition.origin.top.right
         class="fixed inset-x-4 top-12 z-40 rounded-lg border border-line bg-elevated shadow-lg sm:absolute sm:inset-x-auto sm:top-full sm:right-0 sm:mt-1 sm:w-96">
        <div class="flex items-center justify-between gap-2 border-b border-line px-3 py-2">
            <p class="text-sm font-medium text-ink">Avisos</p>
            @if ($this->unreadCount > 0)
                <button type="button" wire:click="markAllAsRead" class="text-xs text-ink-subtle hover:text-ink">Marcar todo como leído</button>
            @endif
        </div>

        <div class="max-h-[60vh] overflow-y-auto p-1">
            @forelse ($this->latest as $notification)
                <div wire:key="bell-{{ $notification->id }}" class="flex">
                    @include('livewire.notifications.partials.item', ['notification' => $notification, 'compact' => true])
                </div>
            @empty
                <p class="px-3 py-8 text-center text-sm text-ink-muted">No tienes avisos.</p>
            @endforelse
        </div>

        <a href="{{ route('notifications.index') }}" wire:navigate
           class="block border-t border-line px-3 py-2 text-center text-sm text-ink-muted hover:bg-hover hover:text-ink">
            Ver bandeja
        </a>
    </div>
</div>
