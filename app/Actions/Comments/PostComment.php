<?php

namespace App\Actions\Comments;

use App\Models\Concerns\Commentable;
use App\Models\Concerns\IsComment;
use App\Models\User;
use App\Support\Mentions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Posts a comment or a reply (F-07, RN-10). A reply to a reply joins the
 * thread of its root, so threads stay one level deep. The author and the
 * people mentioned start following the element, and the alerts go out.
 * The caller authorizes `collaborate` first.
 */
class PostComment
{
    public function __construct(private Mentions $mentions, private NotifyCommentRecipients $notifier) {}

    /**
     * @param  (Model&IsComment)|null  $replyTo
     * @return Model&IsComment
     */
    public function handle(Commentable&Model $commentable, User $author, string $body, ?Model $replyTo = null): Model
    {
        $parentId = null;

        if ($replyTo !== null) {
            $root = $replyTo->isReply() ? $replyTo->parent()->withTrashed()->firstOrFail() : $replyTo;
            abort_unless($commentable->comments()->withTrashed()->whereKey($root->getKey())->exists(), 404);
            $parentId = $root->getKey();
        }

        ['body' => $body, 'user_ids' => $mentionedIds] = $this->mentions->canonicalize(trim($body), $commentable->mentionableUsers());

        /** @var Model&IsComment $comment */
        $comment = DB::transaction(function () use ($commentable, $author, $body, $parentId, $mentionedIds): Model {
            $comment = $commentable->comments()->create([
                'user_id' => $author->id,
                'parent_id' => $parentId,
                'body' => $body,
            ]);

            $comment->mentions()->sync($mentionedIds);
            $commentable->recordCommentActivity($author, $comment);
            $commentable->followIfNotOptedOut([$author->id, ...$mentionedIds]);

            return $comment;
        });

        $this->notifier->handle($commentable, $comment, $author, $mentionedIds);

        return $comment;
    }
}
