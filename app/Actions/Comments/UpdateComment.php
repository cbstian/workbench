<?php

namespace App\Actions\Comments;

use App\Enums\CommentThread;
use App\Models\Concerns\IsComment;
use App\Models\User;
use App\Support\Mentions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Edits a comment (F-07). Only the people mentioned for the first time are
 * alerted. The caller checks the comment is the actor's own.
 */
class UpdateComment
{
    public function __construct(private Mentions $mentions, private NotifyCommentRecipients $notifier) {}

    /**
     * @param  Model&IsComment  $comment
     */
    public function handle(Model $comment, User $actor, string $body): void
    {
        $commentable = $comment->commentable();

        ['body' => $body, 'user_ids' => $mentionedIds] = $this->mentions->canonicalize(trim($body), $commentable->mentionableUsers($comment->getAttribute('thread') ?? CommentThread::Internal));

        $newlyMentioned = DB::transaction(function () use ($comment, $commentable, $body, $mentionedIds): array {
            $previous = $comment->mentions()->pluck('users.id')->map(fn ($id): int => (int) $id)->all();

            $comment->update(['body' => $body, 'edited_at' => now()]);
            $comment->mentions()->sync($mentionedIds);

            $newlyMentioned = array_values(array_diff($mentionedIds, $previous));
            $commentable->followIfNotOptedOut($newlyMentioned);

            return $newlyMentioned;
        });

        if ($newlyMentioned !== []) {
            $this->notifier->handle($commentable, $comment, $actor, $newlyMentioned, onlyMentions: true);
        }
    }
}
