<?php

namespace App\Livewire\Comments;

use App\Actions\Comments\DeleteComment;
use App\Actions\Comments\PostComment;
use App\Actions\Comments\UpdateComment;
use App\Enums\CommentThread;
use App\Models\Page;
use App\Models\PageComment;
use App\Models\Task;
use App\Models\TaskComment;
use App\Support\Mentions;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

/**
 * Conversation of a task or a wiki page (F-07, RN-10): comments in one-level
 * threads, `@mentions` and a follow toggle. Whoever may see the element comments
 * and replies; authors edit and delete their own comments and moderators
 * delete any.
 *
 * Each element has two conversations (P-04): the team's internal thread and
 * the one with the client, which client users read and write in their portal.
 */
class Thread extends Component
{
    use Interactions;

    public const int MAX_LENGTH = 10000;

    #[Locked]
    public Task|Page $commentable;

    #[Locked]
    public CommentThread $thread = CommentThread::Internal;

    /**
     * Hides the section heading when a parent already titles the conversation.
     */
    #[Locked]
    public bool $bare = false;

    public string $body = '';

    #[Locked]
    public ?int $replyingToId = null;

    public string $replyBody = '';

    #[Locked]
    public ?int $editingId = null;

    public string $editingBody = '';

    public function mount(): void
    {
        $this->authorize($this->thread->viewAbility(), $this->commentable);
    }

    /**
     * Root comments, oldest first, each with its replies. A trashed root
     * stays while it still has replies, shown as deleted.
     *
     * @return Collection<int, TaskComment|PageComment>
     */
    #[Computed]
    public function threads(): Collection
    {
        return $this->comments()
            ->withTrashed()
            ->whereNull('parent_id')
            ->where(fn (Builder $query) => $query->whereNull('deleted_at')->orWhereHas('replies'))
            ->with(['user:id,name', 'replies.user:id,name'])
            ->oldest()
            ->get();
    }

    /**
     * Current names of everybody mentioned on screen, keyed by uuid.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function mentionNames(): array
    {
        return app(Mentions::class)->names(
            $this->threads->flatMap(fn ($root) => [$root, ...$root->replies])->pluck('body')
        );
    }

    /**
     * People who can be mentioned, for the autocomplete.
     *
     * @return list<array{id: string, name: string}>
     */
    #[Computed]
    public function mentionables(): array
    {
        return $this->commentable->mentionableUsers($this->thread)
            ->reject(fn ($user): bool => $user->id === auth()->id())
            ->map(fn ($user): array => ['id' => $user->uuid, 'name' => $user->name])
            ->values()
            ->all();
    }

    #[Computed]
    public function canComment(): bool
    {
        return auth()->user()->can($this->thread->commentAbility(), $this->commentable);
    }

    /**
     * The reader is a client user, who reaches the element only through the portal.
     */
    #[Computed]
    public function readsFromPortal(): bool
    {
        return auth()->user()->cannot('view', $this->commentable);
    }

    #[Computed]
    public function isFollowing(): bool
    {
        return $this->commentable->isFollowedBy(auth()->user());
    }

    public function canManage(TaskComment|PageComment $comment): bool
    {
        return ($comment->user_id === auth()->id() && $this->canComment)
            || auth()->user()->can('moderate', $this->commentable);
    }

    public function bodyHtml(TaskComment|PageComment $comment): HtmlString
    {
        return app(Mentions::class)->render($comment->body, $this->mentionNames);
    }

    public function add(PostComment $postComment): void
    {
        $this->authorize($this->thread->commentAbility(), $this->commentable);

        $validated = $this->validate(['body' => ['required', 'string', 'max:'.self::MAX_LENGTH]], attributes: ['body' => 'comentario']);

        $postComment->handle($this->commentable, auth()->user(), $validated['body'], thread: $this->thread);

        $this->reset('body');
        $this->refreshThread();
    }

    public function reply(int $id): void
    {
        $this->authorize($this->thread->commentAbility(), $this->commentable);

        $comment = $this->comments()->withTrashed()->findOrFail($id);

        $this->replyingToId = $comment->parent_id ?? $comment->id;
        $this->reset('replyBody');
    }

    public function cancelReply(): void
    {
        $this->reset('replyingToId', 'replyBody');
    }

    public function addReply(PostComment $postComment): void
    {
        $this->authorize($this->thread->commentAbility(), $this->commentable);

        $root = $this->comments()->withTrashed()->findOrFail((int) $this->replyingToId);

        $validated = $this->validate(['replyBody' => ['required', 'string', 'max:'.self::MAX_LENGTH]], attributes: ['replyBody' => 'respuesta']);

        $postComment->handle($this->commentable, auth()->user(), $validated['replyBody'], $root);

        $this->reset('replyingToId', 'replyBody');
        $this->refreshThread();
    }

    public function edit(int $id): void
    {
        $comment = $this->findOwn($id);

        $this->editingId = $comment->id;
        $this->editingBody = app(Mentions::class)->toPlain($comment->body);
    }

    public function saveEdit(UpdateComment $updateComment): void
    {
        $comment = $this->findOwn((int) $this->editingId);

        $validated = $this->validate(['editingBody' => ['required', 'string', 'max:'.self::MAX_LENGTH]], attributes: ['editingBody' => 'comentario']);

        $updateComment->handle($comment, auth()->user(), $validated['editingBody']);

        $this->reset('editingId', 'editingBody');
        unset($this->threads, $this->mentionNames, $this->isFollowing);
    }

    public function cancelEdit(): void
    {
        $this->reset('editingId', 'editingBody');
    }

    public function delete(int $id, DeleteComment $deleteComment): void
    {
        $comment = $this->comments()->findOrFail($id);
        abort_unless($this->canManage($comment), 403);

        $deleteComment->handle($comment, auth()->user());

        $this->refreshThread();
        $this->toast()->success('Comentario enviado a la papelera')->send();
    }

    public function toggleFollow(): void
    {
        $this->authorize($this->thread->viewAbility(), $this->commentable);

        $isFollowing = ! $this->isFollowing;
        $this->commentable->setFollowing(auth()->user(), $isFollowing);

        unset($this->isFollowing);
        $this->toast()->success($isFollowing ? 'Ahora sigues esta conversación' : 'Dejaste de seguir esta conversación')->send();
    }

    /**
     * A comment of the signed-in user, who must still be able to comment.
     */
    private function findOwn(int $id): TaskComment|PageComment
    {
        $this->authorize($this->thread->commentAbility(), $this->commentable);

        return $this->comments()->where('user_id', auth()->id())->findOrFail($id);
    }

    /**
     * Comments of this thread only.
     *
     * @return HasMany<TaskComment|PageComment, Task|Page>
     */
    private function comments(): HasMany
    {
        return $this->commentable->comments()->where('thread', $this->thread);
    }

    private function refreshThread(): void
    {
        unset($this->threads, $this->mentionNames, $this->isFollowing);
        $this->dispatch($this->commentable->commentableType().'-activity-changed');
    }

    public function render(): View
    {
        return view('livewire.comments.thread');
    }
}
