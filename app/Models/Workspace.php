<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use App\Models\Concerns\TracksDeleter;
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
 * The top-level place where clients, projects and documentation live (RN-02),
 * and the tenant boundary of the data model.
 *
 * Soft deletes back the trash bin described in RN-19.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property string $name
 * @property int $created_by_id
 * @property Carbon|null $deleted_at
 * @property int|null $deleted_by_id
 */
#[Fillable(['account_id', 'name', 'created_by_id'])]
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory, HasPublicUuid, SoftDeletes, TracksDeleter;

    /**
     * Account that owns the workspace; its owner is the workspace's super admin.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Users invited to this workspace. The super admin is not a member.
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
     * Workspaces of the accounts the user owns, plus those they were invited to (RN-03).
     *
     * @param  Builder<Workspace>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $query->where(function (Builder $query) use ($user): void {
            $query->whereHas('account', function (Builder $query) use ($user): void {
                $query->where('owner_id', $user->id);
            })->orWhereHas('members', function (Builder $query) use ($user): void {
                $query->whereKey($user->id);
            });
        });
    }
}
