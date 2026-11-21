<?php

namespace App\Models;

use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * The top-level place where clients, projects and documentation live (RN-02).
 *
 * Soft deletes back the trash bin described in RN-19.
 *
 * @property int $id
 * @property int $owner_id
 * @property string $name
 * @property Carbon|null $deleted_at
 */
#[Fillable(['owner_id', 'name'])]
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Super admin who created this workspace.
     *
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Users invited to this workspace. The owner is not a member by default.
     *
     * @return BelongsToMany<User, $this, WorkspaceMember>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_user')
            ->using(WorkspaceMember::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Client, $this>
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * @return HasMany<WikiLabel, $this>
     */
    public function wikiLabels(): HasMany
    {
        return $this->hasMany(WikiLabel::class);
    }

    /**
     * Every page in the workspace, including project wiki pages.
     *
     * @return HasMany<Page, $this>
     */
    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    /**
     * Pages of the workspace wiki. Project pages are excluded.
     *
     * @return HasMany<Page, $this>
     */
    public function workspacePages(): HasMany
    {
        return $this->hasMany(Page::class)->whereNull('project_id');
    }

    /**
     * Workspaces the user owns or was invited to (RN-03).
     *
     * @param  Builder<Workspace>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $query->where(function (Builder $query) use ($user): void {
            $query->whereBelongsTo($user, 'owner')
                ->orWhereHas('members', function (Builder $query) use ($user): void {
                    $query->whereKey($user->id);
                });
        });
    }
}
