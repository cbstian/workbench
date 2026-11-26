<?php

namespace App\Notifications\Comments;

use App\Models\Concerns\Commentable;
use App\Models\Concerns\IsComment;
use App\Models\User;
use App\Support\Mentions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

/**
 * A comment-related alert in the in-app inbox (RN-10). The payload is built
 * once, so the notification can be queued or sent through more channels
 * (mail, broadcast) later without touching the callers.
 */
abstract class CommentNotification extends Notification
{
    /**
     * @var array{kind: string, comment_uuid: string, commentable_type: string, commentable_uuid: string, title: string, context: string, workspace_id: int, actor_id: int, actor_name: string, excerpt: string}
     */
    public readonly array $payload;

    /**
     * @param  Model&IsComment  $comment
     */
    public function __construct(Model $comment, Commentable&Model $commentable, User $actor)
    {
        $this->payload = [
            'kind' => static::kind(),
            'comment_uuid' => (string) $comment->getAttribute('uuid'),
            'commentable_type' => $commentable->commentableType(),
            'commentable_uuid' => (string) $commentable->getAttribute('uuid'),
            'title' => $commentable->commentableTitle(),
            'context' => $commentable->commentableContext(),
            'workspace_id' => (int) $commentable->getAttribute('workspace_id'),
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
            'excerpt' => app(Mentions::class)->excerpt((string) $comment->getAttribute('body')),
        ];
    }

    /**
     * `mention`, `reply` or `follow`.
     */
    abstract public static function kind(): string;

    /**
     * What happened, in Spanish, after the actor's name.
     */
    abstract public static function action(): string;

    /**
     * The action text of a stored notification, by its kind.
     */
    public static function actionForKind(string $kind): string
    {
        return match ($kind) {
            MentionedInComment::kind() => MentionedInComment::action(),
            RepliedToYourComment::kind() => RepliedToYourComment::action(),
            default => CommentOnFollowedItem::action(),
        };
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Stored in the `type` column, so the inbox can filter by kind.
     */
    public function databaseType(object $notifiable): string
    {
        return 'comment.'.static::kind();
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->payload;
    }
}
