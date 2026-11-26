<?php

namespace App\Livewire\Notifications;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The user's inbox (RN-10): mentions, replies and comments on what they
 * follow, across every workspace, newest first.
 */
#[Title('Bandeja')]
class Index extends Component
{
    use WithPagination;

    /**
     * Allowed values of the kind filter, with their Spanish names.
     *
     * @var array<string, string>
     */
    public const array KINDS = [
        'mention' => 'Menciones',
        'reply' => 'Respuestas',
        'follow' => 'Seguidos',
    ];

    #[Url(except: 'unread')]
    public string $status = 'unread';

    #[Url(except: '')]
    public string $kind = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, DatabaseNotification>
     */
    #[Computed]
    public function notifications(): LengthAwarePaginator
    {
        return auth()->user()->notifications()
            ->when($this->status !== 'all', fn ($query) => $query->whereNull('read_at'))
            ->when(array_key_exists($this->kind, self::KINDS), fn ($query) => $query->where('type', 'comment.'.$this->kind))
            ->latest()
            ->paginate(20);
    }

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    #[Computed]
    public function kindOptions(): array
    {
        return collect(self::KINDS)->map(fn (string $label, string $value): array => ['label' => $label, 'value' => $value])->values()->all();
    }

    public function markAsRead(string $id): void
    {
        auth()->user()->notifications()->whereKey($id)->firstOrFail()->markAsRead();
        $this->changed();
    }

    public function markAsUnread(string $id): void
    {
        auth()->user()->notifications()->whereKey($id)->firstOrFail()->markAsUnread();
        $this->changed();
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
        $this->changed();
    }

    private function changed(): void
    {
        unset($this->notifications, $this->unreadCount);
        $this->dispatch('notifications-changed');
    }

    public function render(): View
    {
        return view('livewire.notifications.index');
    }
}
