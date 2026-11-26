<?php

namespace App\Livewire\Comments;

use App\Enums\CommentThread;
use App\Models\Page;
use App\Models\Task;
use App\Support\ClientPortal;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The team's view of a task or page conversations (P-04): the internal thread
 * and, when the element is shown to the client or already has messages from
 * it, the conversation with the client in a second tab.
 *
 * Opens on the client tab with `?hilo=cliente` (inbox alerts use it).
 */
class Conversations extends Component
{
    #[Locked]
    public Task|Page $commentable;

    public function mount(): void
    {
        $this->authorize('view', $this->commentable);
    }

    #[Computed]
    public function showsClientThread(): bool
    {
        return app(ClientPortal::class)->isExposed($this->commentable)
            || $this->counts[CommentThread::Client->value] > 0;
    }

    /**
     * Comments per thread, trashed ones excluded.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function counts(): array
    {
        $counts = $this->commentable->comments()
            ->toBase()
            ->selectRaw('thread, count(*) as total')
            ->groupBy('thread')
            ->pluck('total', 'thread');

        return [
            CommentThread::Internal->value => (int) ($counts[CommentThread::Internal->value] ?? 0),
            CommentThread::Client->value => (int) ($counts[CommentThread::Client->value] ?? 0),
        ];
    }

    public function render(): View
    {
        return view('livewire.comments.conversations');
    }
}
