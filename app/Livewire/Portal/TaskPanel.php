<?php

namespace App\Livewire\Portal;

use App\Enums\CommentThread;
use App\Enums\TaskActivityEvent;
use App\Models\Task;
use App\Models\TaskActivity;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * A task as the client sees it (P-04, decidida 2026-11-26): its data,
 * assignees, subtasks, history and the conversation with the client. Never
 * its attachments nor the internal comments.
 */
class TaskPanel extends Component
{
    /**
     * History events the client never sees: attachments are not shown in
     * the portal.
     *
     * @var list<TaskActivityEvent>
     */
    public const array HIDDEN_EVENTS = [
        TaskActivityEvent::AttachmentAdded,
        TaskActivityEvent::AttachmentDeleted,
    ];

    #[Locked]
    public Task $task;

    public function mount(): void
    {
        $this->authorize('viewInPortal', $this->task);

        $this->task->loadMissing(['status', 'priority', 'labels:id,name,color', 'assignees:id,name', 'parent:id,uuid,name']);
    }

    /**
     * @return Collection<int, Task>
     */
    #[Computed]
    public function subtasks(): Collection
    {
        return $this->task->subtasks()->with(['status', 'assignees:id,name'])->orderBy('position')->orderBy('id')->get();
    }

    /**
     * History, newest first, without attachments nor internal comments.
     *
     * @return Collection<int, TaskActivity>
     */
    #[Computed]
    public function activities(): Collection
    {
        $clientCommentIds = $this->task->comments()->withTrashed()
            ->where('thread', CommentThread::Client)
            ->pluck('id')
            ->all();

        return $this->task->activities()
            ->with('user:id,name')
            ->whereNotIn('event', self::HIDDEN_EVENTS)
            ->latest('created_at')
            ->latest('id')
            ->limit(100)
            ->get()
            ->reject(fn (TaskActivity $activity): bool => in_array($activity->event, [TaskActivityEvent::CommentAdded, TaskActivityEvent::CommentDeleted], true)
                && ! in_array((int) ($activity->metadata['comment_id'] ?? 0), $clientCommentIds, true))
            ->values();
    }

    #[On('task-activity-changed')]
    public function refreshActivities(): void
    {
        unset($this->activities);
    }

    public function render(): View
    {
        return view('livewire.portal.task-panel');
    }
}
