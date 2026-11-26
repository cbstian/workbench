<?php

namespace App\Livewire\Notifications;

use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Inbox bell in the topbar (RN-10): unread count and the latest alerts,
 * refreshed every 30 seconds while the page is visible.
 */
class Bell extends Component
{
    public const int LATEST = 8;

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    #[Computed]
    public function latest(): Collection
    {
        return auth()->user()->notifications()->latest()->limit(self::LATEST)->get();
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        $this->refresh();
        $this->dispatch('notifications-changed');
    }

    #[On('notifications-changed')]
    public function refresh(): void
    {
        unset($this->unreadCount, $this->latest);
    }

    public function render(): View
    {
        return view('livewire.notifications.bell');
    }
}
