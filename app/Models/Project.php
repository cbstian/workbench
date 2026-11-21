<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use App\Models\Concerns\TracksDeleter;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A unit of work with name, description and icon (docs/flujo-canonico.md, RN-13).
 *
 * Soft deletes back the trash bin described in RN-19.
 *
 * @property int $id
 * @property string $uuid
 * @property int $workspace_id
 * @property int|null $client_id
 * @property string $name
 * @property string|null $description
 * @property string|null $icon
 * @property bool $is_visible_to_client
 * @property int $created_by_id
 * @property Carbon|null $deleted_at
 * @property int|null $deleted_by_id
 */
#[Fillable(['workspace_id', 'client_id', 'name', 'description', 'icon', 'is_visible_to_client', 'created_by_id'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasPublicUuid, SoftDeletes, TracksDeleter;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_visible_to_client' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Super admin who created the project (RN-13).
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Admins and members assigned to this project.
     *
     * @return BelongsToMany<User, $this, ProjectMember>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user')->using(ProjectMember::class);
    }

    /**
     * @return HasMany<ProjectStatus, $this>
     */
    public function statuses(): HasMany
    {
        return $this->hasMany(ProjectStatus::class)->orderBy('position');
    }

    /**
     * @return HasMany<ProjectPriority, $this>
     */
    public function priorities(): HasMany
    {
        return $this->hasMany(ProjectPriority::class)->orderBy('position');
    }

    /**
     * Status applied to a new task.
     *
     * @return HasOne<ProjectStatus, $this>
     */
    public function initialStatus(): HasOne
    {
        return $this->hasOne(ProjectStatus::class)->where('is_initial', true);
    }

    /**
     * Priority applied to a new task.
     *
     * @return HasOne<ProjectPriority, $this>
     */
    public function initialPriority(): HasOne
    {
        return $this->hasOne(ProjectPriority::class)->where('is_initial', true);
    }

    /**
     * @return HasMany<TaskLabel, $this>
     */
    public function taskLabels(): HasMany
    {
        return $this->hasMany(TaskLabel::class);
    }

    /**
     * Tasks and subtasks of this project.
     *
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Tasks that are not subtasks.
     *
     * @return HasMany<Task, $this>
     */
    public function rootTasks(): HasMany
    {
        return $this->hasMany(Task::class)->whereNull('parent_id');
    }

    /**
     * @return HasMany<Page, $this>
     */
    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    /**
     * Filter projects whose name or description contains the given term.
     *
     * @param  Builder<Project>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $query->when(filled($term), function (Builder $query) use ($term): void {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], (string) $term).'%';

            $query->where(function (Builder $query) use ($like): void {
                $query->whereLike('name', $like)->orWhereLike('description', $like);
            });
        });
    }
}
