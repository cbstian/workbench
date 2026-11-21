<?php

namespace App\Models;

use App\Enums\TaskActivityEvent;
use App\Models\Concerns\InheritsWorkspace;
use Database\Factories\TaskActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One recorded change of a task. A history entry, not a content version (RN-08).
 *
 * Append-only: the database rejects updates and deletes. `changes` holds
 * `{"field": {"old": ..., "new": ...}}` for the fields that changed.
 *
 * @property int $id
 * @property int $workspace_id
 * @property int $task_id
 * @property int|null $user_id
 * @property TaskActivityEvent $event
 * @property array<string, array{old: mixed, new: mixed}>|null $changes
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 */
#[Fillable(['task_id', 'user_id', 'event', 'changes', 'metadata'])]
class TaskActivity extends Model
{
    /** @use HasFactory<TaskActivityFactory> */
    use HasFactory, InheritsWorkspace;

    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event' => TaskActivityEvent::class,
            'changes' => 'array',
            'metadata' => 'array',
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
     * User who made the change; null for console actions.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
