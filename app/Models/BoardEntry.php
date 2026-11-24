<?php

namespace App\Models;

use App\Enums\BoardEntryStatus;
use App\Models\Concerns\HasPublicUuid;
use App\Models\Concerns\InheritsWorkspace;
use Database\Factories\BoardEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A short note on the workspace board, for one project on one day (E11).
 *
 * Several notes can share a cell. Each is planned or done, and may point at
 * a task of the same project. The creator is always recorded.
 *
 * @property int $id
 * @property string $uuid
 * @property int $workspace_id
 * @property int $project_id
 * @property int|null $task_id
 * @property Carbon $occurs_on
 * @property string $body
 * @property BoardEntryStatus $status
 * @property int $position
 * @property int $created_by_id
 * @property Carbon|null $completed_at
 * @property int|null $completed_by_id
 */
#[Fillable([
    'project_id',
    'task_id',
    'occurs_on',
    'body',
    'status',
    'position',
    'created_by_id',
    'completed_at',
    'completed_by_id',
])]
class BoardEntry extends Model
{
    /** @use HasFactory<BoardEntryFactory> */
    use HasFactory, HasPublicUuid, InheritsWorkspace;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurs_on' => 'date',
            'status' => BoardEntryStatus::class,
            'position' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    protected function inheritedWorkspaceId(): ?int
    {
        return Project::withTrashed()->whereKey($this->project_id)->value('workspace_id');
    }

    /**
     * A planned note whose day is before today. Done notes are never overdue.
     *
     * Today follows the application timezone (America/Santiago).
     */
    public function isOverdue(): bool
    {
        return $this->status === BoardEntryStatus::Planned
            && $this->occurs_on->toDateString() < today()->toDateString();
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Task or subtask of the same project, when the note is linked to one.
     *
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * User who wrote the note.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * User who marked the note done, when it is done.
     *
     * @return BelongsTo<User, $this>
     */
    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_id');
    }
}
