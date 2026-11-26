<?php

namespace App\Models;

use App\Enums\CommentThread;
use App\Enums\TaskActivityEvent;
use App\Models\Concerns\Commentable;
use App\Models\Concerns\HasComments;
use App\Models\Concerns\HasPublicUuid;
use App\Models\Concerns\InheritsWorkspace;
use App\Models\Concerns\TracksDeleter;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Work inside a project. A subtask is a task whose parent is a root task (RN-17).
 *
 * The database keeps the status, priority, parent, labels and assignees inside
 * the project, and sets `completed_at` when the status category is closed.
 *
 * @property int $id
 * @property string $uuid
 * @property int $workspace_id
 * @property int $project_id
 * @property int|null $parent_id
 * @property int $status_id
 * @property int $priority_id
 * @property string $name
 * @property string|null $description
 * @property Carbon|null $starts_on
 * @property Carbon|null $ends_on
 * @property int $position
 * @property Carbon|null $completed_at
 * @property int $created_by_id
 * @property Carbon|null $deleted_at
 * @property int|null $deleted_by_id
 */
#[Fillable([
    'project_id',
    'parent_id',
    'status_id',
    'priority_id',
    'name',
    'description',
    'starts_on',
    'ends_on',
    'position',
    'created_by_id',
])]
#[Hidden(['search_vector'])]
class Task extends Model implements Commentable
{
    /** @use HasFactory<TaskFactory> */
    use HasComments, HasFactory, HasPublicUuid, InheritsWorkspace, SoftDeletes, TracksDeleter;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'position' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    protected function inheritedWorkspaceId(): ?int
    {
        return Project::withTrashed()->whereKey($this->project_id)->value('workspace_id');
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')->orderBy('position');
    }

    /**
     * Board notes linked to this task.
     *
     * @return HasMany<BoardEntry, $this>
     */
    public function boardEntries(): HasMany
    {
        return $this->hasMany(BoardEntry::class);
    }

    /**
     * Open tasks whose end date is before today (the kanban overdue rule).
     *
     * Closed tasks carry completed_at, including cancelled ones.
     *
     * @param  Builder<Task>  $query
     */
    public function scopeOpenPastDue(Builder $query): void
    {
        $query->whereNull('completed_at')
            ->whereNotNull('ends_on')
            ->where('ends_on', '<', today()->toDateString());
    }

    /**
     * @return BelongsTo<ProjectStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(ProjectStatus::class, 'status_id');
    }

    /**
     * @return BelongsTo<ProjectPriority, $this>
     */
    public function priority(): BelongsTo
    {
        return $this->belongsTo(ProjectPriority::class, 'priority_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Project team members assigned to this task. Never a client user (RN-18).
     *
     * @return BelongsToMany<User, $this, TaskAssignee>
     */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_user')->using(TaskAssignee::class);
    }

    /**
     * @return BelongsToMany<TaskLabel, $this, TaskLabelAssignment>
     */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(TaskLabel::class, 'task_task_label')->using(TaskLabelAssignment::class);
    }

    /**
     * @return HasMany<TaskComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    /**
     * @return HasMany<TaskAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    /**
     * @return HasMany<TaskActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(TaskActivity::class);
    }

    protected function followersTable(): string
    {
        return 'task_followers';
    }

    /**
     * Whoever can read the thread: its project team and the super admin, and
     * in the client thread also the users of the project's client.
     *
     * @return Collection<int, User>
     */
    public function mentionableUsers(CommentThread $thread = CommentThread::Internal): Collection
    {
        return $this->filterMentionable([
            ...$this->project->members()->get(),
            $this->accountOwner($this->workspace_id),
            ...($thread === CommentThread::Client ? $this->project->client?->users()->get() ?? [] : []),
        ], $thread);
    }

    /**
     * Description as safe HTML (Markdown with raw HTML escaped).
     */
    public function descriptionHtml(): string
    {
        return filled($this->description)
            ? Str::markdown($this->description, ['html_input' => 'escape', 'allow_unsafe_links' => false])
            : '';
    }

    /**
     * The creator and the assignees follow the task by default.
     *
     * @return list<int>
     */
    public function implicitFollowerIds(): array
    {
        return array_values(array_unique(array_filter([
            $this->created_by_id,
            ...$this->assignees()->pluck('users.id')->map(fn ($id): int => (int) $id)->all(),
        ])));
    }

    public function commentableType(): string
    {
        return 'task';
    }

    public function commentableTitle(): string
    {
        return $this->name;
    }

    public function commentableContext(): string
    {
        return $this->project?->name ?? '';
    }

    public function commentableUrl(): string
    {
        return route('tasks.show', $this);
    }

    public function recordCommentActivity(User $actor, Model $comment, bool $isDeletion = false): void
    {
        TaskActivity::record($this, $actor, $isDeletion ? TaskActivityEvent::CommentDeleted : TaskActivityEvent::CommentAdded, metadata: ['comment_id' => $comment->getKey()]);
    }
}
