<?php

namespace App\Models\Concerns;

use App\Enums\CommentThread;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Something people comment on, mention each other in and follow: a task or
 * a wiki page. Its policy answers `view`, `comment` and
 * `moderate` (delete anybody's comment).
 */
interface Commentable
{
    /**
     * Comments and replies, trashed ones excluded.
     *
     * @return HasMany<Model, $this>
     */
    public function comments(): HasMany;

    /**
     * Explicit follow choices; `is_following = false` remembers an unfollow.
     *
     * @return BelongsToMany<User, $this>
     */
    public function followers(): BelongsToMany;

    /**
     * Active users who can read the given thread and therefore can be
     * mentioned in it. Client users only take part in the client thread.
     *
     * @return Collection<int, User>
     */
    public function mentionableUsers(CommentThread $thread = CommentThread::Internal): Collection;

    /**
     * Users who follow it by their role (creator, assignees) unless they unfollowed.
     *
     * @return list<int>
     */
    public function implicitFollowerIds(): array;

    /**
     * Short key stored in notifications: `task` or `page`.
     */
    public function commentableType(): string;

    /**
     * Name shown in notifications.
     */
    public function commentableTitle(): string;

    /**
     * Where it lives (project name, or the workspace wiki).
     */
    public function commentableContext(): string;

    public function commentableUrl(): string;

    public function recordCommentActivity(User $actor, Model $comment, bool $isDeletion = false): void;
}
