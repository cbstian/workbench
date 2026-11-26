<?php

namespace App\Notifications\Comments;

/**
 * Somebody replied in a thread the user started or took part in.
 */
class RepliedToYourComment extends CommentNotification
{
    public static function kind(): string
    {
        return 'reply';
    }

    public static function action(): string
    {
        return 'respondió en un hilo tuyo en';
    }
}
