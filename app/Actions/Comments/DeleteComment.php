<?php

namespace App\Actions\Comments;

use App\Models\Concerns\IsComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Sends a comment to the trash (RN-19). Its replies stay; the thread shows
 * the root as deleted while it has replies. The caller authorizes it.
 */
class DeleteComment
{
    /**
     * @param  Model&IsComment  $comment
     */
    public function handle(Model $comment, User $actor): void
    {
        DB::transaction(function () use ($comment, $actor): void {
            $comment->delete();
            $comment->commentable()->recordCommentActivity($actor, $comment, isDeletion: true);
        });
    }
}
