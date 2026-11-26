<?php

namespace App\Models;

use App\Enums\CommentThread;
use App\Models\Concerns\Commentable;
use App\Models\Concerns\HasPublicUuid;
use App\Models\Concerns\InheritsWorkspace;
use App\Models\Concerns\IsComment;
use App\Models\Concerns\TracksDeleter;
use Database\Factories\PageCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A message left on a wiki page, in one-level threads, with mentions (RN-10).
 *
 * @property int $id
 * @property string $uuid
 * @property int $workspace_id
 * @property int $page_id
 * @property int|null $parent_id
 * @property CommentThread $thread
 * @property int $user_id
 * @property string $body
 * @property Carbon|null $edited_at
 * @property Carbon|null $deleted_at
 * @property int|null $deleted_by_id
 */
#[Fillable(['page_id', 'parent_id', 'thread', 'user_id', 'body', 'edited_at'])]
class PageComment extends Model
{
    /** @use HasFactory<PageCommentFactory> */
    use HasFactory, HasPublicUuid, InheritsWorkspace, IsComment, SoftDeletes, TracksDeleter;

    protected function inheritedWorkspaceId(): ?int
    {
        return Page::withTrashed()->whereKey($this->page_id)->value('workspace_id');
    }

    protected function mentionsTable(): string
    {
        return 'page_comment_mentions';
    }

    public function commentable(): Commentable
    {
        return $this->page;
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
