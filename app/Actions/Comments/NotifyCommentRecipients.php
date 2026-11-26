<?php

namespace App\Actions\Comments;

use App\Models\Concerns\Commentable;
use App\Models\Concerns\IsComment;
use App\Models\User;
use App\Notifications\Comments\CommentNotification;
use App\Notifications\Comments\CommentOnFollowedItem;
use App\Notifications\Comments\MentionedInComment;
use App\Notifications\Comments\RepliedToYourComment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;

/**
 * Sends the inbox alerts of a comment (RN-10): one per person, the most
 * specific one first (mention, then reply, then follow). Never to the
 * author, and only to active users who can still see the element.
 */
class NotifyCommentRecipients
{
    /**
     * @param  Model&IsComment  $comment
     * @param  list<int>  $mentionedIds  users to alert as mentioned
     * @param  bool  $onlyMentions  an edit alerts only newly mentioned users
     */
    public function handle(Commentable&Model $commentable, Model $comment, User $actor, array $mentionedIds, bool $onlyMentions = false): void
    {
        /** @var array<int, class-string<CommentNotification>> $recipients */
        $recipients = [];

        $add = function (iterable $userIds, string $notification) use (&$recipients): void {
            foreach ($userIds as $userId) {
                $recipients[(int) $userId] ??= $notification;
            }
        };

        $add($mentionedIds, MentionedInComment::class);

        if (! $onlyMentions) {
            if ($comment->isReply()) {
                $add($this->threadParticipantIds($comment), RepliedToYourComment::class);
            }

            $add($commentable->followerIds(), CommentOnFollowedItem::class);
        }

        unset($recipients[$actor->id]);

        if ($recipients === []) {
            return;
        }

        $users = User::query()->whereKey(array_keys($recipients))->get()
            ->filter(fn (User $user): bool => $user->isActive() && $user->can('view', $commentable));

        foreach ($users as $user) {
            Notification::send($user, new $recipients[$user->id]($comment, $commentable, $actor));
        }
    }

    /**
     * Author of the root comment and everybody who replied in the thread.
     *
     * @param  Model&IsComment  $comment
     * @return list<int>
     */
    private function threadParticipantIds(Model $comment): array
    {
        $root = $comment->parent()->withTrashed()->first();

        if ($root === null) {
            return [];
        }

        return array_values(array_unique([
            (int) $root->getAttribute('user_id'),
            ...$root->replies()->pluck('user_id')->map(fn ($id): int => (int) $id)->all(),
        ]));
    }
}
