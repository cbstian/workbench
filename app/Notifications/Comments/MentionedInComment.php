<?php

namespace App\Notifications\Comments;

/**
 * Somebody mentioned the user in a comment.
 */
class MentionedInComment extends CommentNotification
{
    public static function kind(): string
    {
        return 'mention';
    }

    public static function action(): string
    {
        return 'te mencionó en';
    }
}
