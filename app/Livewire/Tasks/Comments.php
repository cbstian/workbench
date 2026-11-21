<?php

namespace App\Livewire\Tasks;

use App\Enums\TaskActivityEvent;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskComment;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

/**
 * Conversation of a task (F-07). Whoever may act on the task comments; the
 * author edits and deletes their own comment, and admins delete any. No
 * mentions or e-mail notifications (RN-10).
 */
class Comments extends Component
{
    use Interactions;

    #[Locked]
    public Task $task;

    public string $body = '';

    #[Locked]
    public ?int $editingId = null;

    public string $editingBody = '';

    public function mount(): void
    {
        $this->authorize('view', $this->task);
    }

    /**
     * @return Collection<int, TaskComment>
     */
    #[Computed]
    public function comments(): Collection
    {
        return $this->task->comments()->with('user:id,name')->oldest()->get();
    }

    #[Computed]
    public function canComment(): bool
    {
        return auth()->user()->can('collaborate', $this->task);
    }

    public function canManage(TaskComment $comment): bool
    {
        return ($comment->user_id === auth()->id() && $this->canComment)
            || auth()->user()->can('moderate', $this->task);
    }

    public function add(): void
    {
        $this->authorize('collaborate', $this->task);

        $validated = $this->validate(['body' => ['required', 'string', 'max:10000']], attributes: ['body' => 'comentario']);

        DB::transaction(function () use ($validated): void {
            $comment = $this->task->comments()->create(['user_id' => auth()->id(), 'body' => trim($validated['body'])]);
            TaskActivity::record($this->task, auth()->user(), TaskActivityEvent::CommentAdded, metadata: ['comment_id' => $comment->id]);
        });

        $this->body = '';
        $this->refreshTask();
    }

    public function edit(int $id): void
    {
        $comment = $this->findOwn($id);

        $this->editingId = $comment->id;
        $this->editingBody = $comment->body;
    }

    public function saveEdit(): void
    {
        $comment = $this->findOwn((int) $this->editingId);

        $validated = $this->validate(['editingBody' => ['required', 'string', 'max:10000']], attributes: ['editingBody' => 'comentario']);

        $comment->update(['body' => trim($validated['editingBody']), 'edited_at' => now()]);

        $this->reset('editingId', 'editingBody');
        unset($this->comments);
    }

    public function cancelEdit(): void
    {
        $this->reset('editingId', 'editingBody');
    }

    public function delete(int $id): void
    {
        $comment = $this->task->comments()->findOrFail($id);
        abort_unless($this->canManage($comment), 403);

        DB::transaction(function () use ($comment): void {
            $comment->delete();
            TaskActivity::record($this->task, auth()->user(), TaskActivityEvent::CommentDeleted, metadata: ['comment_id' => $comment->id]);
        });

        $this->refreshTask();
        $this->toast()->success('Comentario enviado a la papelera')->send();
    }

    /**
     * A comment of the signed-in user, who must still be able to comment.
     */
    private function findOwn(int $id): TaskComment
    {
        $this->authorize('collaborate', $this->task);

        return $this->task->comments()->where('user_id', auth()->id())->findOrFail($id);
    }

    private function refreshTask(): void
    {
        unset($this->comments);
        $this->dispatch('task-activity-changed');
    }

    public function render(): View
    {
        return view('livewire.tasks.comments');
    }
}
