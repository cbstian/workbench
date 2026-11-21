<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use App\Models\Concerns\InheritsWorkspace;
use App\Models\Concerns\TracksDeleter;
use Database\Factories\TaskCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A message left on a task (RN-07). Mentions are out of scope (RN-10).
 *
 * @property int $id
 * @property string $uuid
 * @property int $workspace_id
 * @property int $task_id
 * @property int $user_id
 * @property string $body
 * @property Carbon|null $edited_at
 * @property Carbon|null $deleted_at
 * @property int|null $deleted_by_id
 */
#[Fillable(['task_id', 'user_id', 'body', 'edited_at'])]
class TaskComment extends Model
{
    /** @use HasFactory<TaskCommentFactory> */
    use HasFactory, HasPublicUuid, InheritsWorkspace, SoftDeletes, TracksDeleter;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
        ];
    }

    protected function inheritedWorkspaceId(): ?int
    {
        return Task::withTrashed()->whereKey($this->task_id)->value('workspace_id');
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Author of the comment.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
