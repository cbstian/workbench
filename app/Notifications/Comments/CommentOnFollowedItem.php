<?php

namespace App\Notifications\Comments;

/**
 * Somebody commented on a task or page the user follows.
 */
class CommentOnFollowedItem extends CommentNotification
{
    public static function kind(): string
    {
        return 'follow';
    }

    public static function action(): string
    {
        return 'comentó en';
    }
}
