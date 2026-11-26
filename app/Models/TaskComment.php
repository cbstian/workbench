<?php

namespace App\Models;

use App\Models\Concerns\Commentable;
use App\Models\Concerns\HasPublicUuid;
use App\Models\Concerns\InheritsWorkspace;
use App\Models\Concerns\IsComment;
use App\Models\Concerns\TracksDeleter;
use Database\Factories\TaskCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A message left on a task (RN-07), in one-level threads, with mentions (RN-10).
 *
 * @property int $id
 * @property string $uuid
 * @property int $workspace_id
 * @property int $task_id
 * @property int|null $parent_id
 * @property int $user_id
 * @property string $body
 * @property Carbon|null $edited_at
 * @property Carbon|null $deleted_at
 * @property int|null $deleted_by_id
 */
#[Fillable(['task_id', 'parent_id', 'user_id', 'body', 'edited_at'])]
class TaskComment extends Model
{
    /** @use HasFactory<TaskCommentFactory> */
    use HasFactory, HasPublicUuid, InheritsWorkspace, IsComment, SoftDeletes, TracksDeleter;

    protected function inheritedWorkspaceId(): ?int
    {
        return Task::withTrashed()->whereKey($this->task_id)->value('workspace_id');
    }

    protected function mentionsTable(): string
    {
        return 'task_comment_mentions';
    }

    public function commentable(): Commentable
    {
        return $this->task;
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
