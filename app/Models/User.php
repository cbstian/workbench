<?php

namespace App\Models;

use App\Enums\ColorPalette;
use App\Enums\Permission;
use App\Enums\WorkspaceRole;
use Closure;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Traits\HasRoles;

/**
 * A person who can sign in. Created from the console, never by open registration.
 *
 * The `uuid` column is filled by the database (`uuidv7()`); users are not in URLs yet.
 *
 * @property int $id
 * @property string $uuid
 * @property int|null $account_id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property ColorPalette|null $palette
 * @property Carbon|null $deactivated_at
 * @property int|null $deactivated_by_id
 * @property Carbon|null $deleted_at
 * @property int|null $deleted_by_id
 */
#[Fillable(['name', 'email', 'password', 'palette', 'deactivated_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'password' => 'hashed',
            'palette' => ColorPalette::class,
        ];
    }

    /**
     * Account that created and manages this user (who may deactivate or delete them).
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Account this user owns as super admin.
     *
     * @return HasOne<Account, $this>
     */
    public function ownedAccount(): HasOne
    {
        return $this->hasOne(Account::class, 'owner_id');
    }

    /**
     * Whether the user is the super admin of an account (RN-01).
     */
    public function isSuperAdmin(): bool
    {
        return $this->ownedAccount !== null;
    }

    /**
     * Whether the user is the super admin of the workspace (owner of its account).
     */
    public function ownsWorkspace(Workspace $workspace): bool
    {
        return $this->ownedAccount !== null && $workspace->account_id === $this->ownedAccount->id;
    }

    /**
     * Whether the user may sign in: not deactivated nor deleted (A-09, A-10).
     */
    public function isActive(): bool
    {
        return $this->deactivated_at === null && ! $this->trashed();
    }

    /**
     * Role of the user in the workspace, or null when they are not a member.
     */
    public function roleIn(Workspace $workspace): ?WorkspaceRole
    {
        $role = $this->workspaces()->whereKey($workspace->id)->first()?->pivot->role;

        return $role instanceof WorkspaceRole ? $role : WorkspaceRole::tryFrom((string) $role);
    }

    /**
     * Whether the user is assigned to the project team.
     */
    public function isOnTeamOf(Project $project): bool
    {
        return $this->projects()->whereKey($project->id)->exists();
    }

    /**
     * Ids of the active projects of the workspace whose team includes the user.
     *
     * @return list<int>
     */
    public function projectIdsIn(Workspace $workspace): array
    {
        return $this->projects()
            ->where('projects.workspace_id', $workspace->id)
            ->pluck('projects.id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * Whether the user's workspace role grants the permission (spatie team = workspace).
     */
    public function hasWorkspacePermission(Workspace|int $workspace, Permission $permission): bool
    {
        return $this->withinWorkspaceTeam($workspace, function () use ($permission): bool {
            try {
                return $this->hasPermissionTo($permission->value);
            } catch (PermissionDoesNotExist) {
                return false;
            }
        });
    }

    /**
     * Run a callback with the spatie team set to the workspace, then restore it.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function withinWorkspaceTeam(Workspace|int $workspace, Closure $callback): mixed
    {
        $previousTeamId = getPermissionsTeamId();

        setPermissionsTeamId($workspace instanceof Workspace ? $workspace->id : $workspace);
        $this->unsetRelation('roles')->unsetRelation('permissions');

        try {
            return $callback();
        } finally {
            setPermissionsTeamId($previousTeamId);
            $this->unsetRelation('roles')->unsetRelation('permissions');
        }
    }

    /**
     * Workspaces of the account this user owns as super admin.
     *
     * @return HasManyThrough<Workspace, Account, $this>
     */
    public function ownedWorkspaces(): HasManyThrough
    {
        return $this->hasManyThrough(Workspace::class, Account::class, 'owner_id', 'account_id');
    }

    /**
     * Workspaces this user was invited to, with the workspace role.
     *
     * @return BelongsToMany<Workspace, $this, WorkspaceMember>
     */
    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_user')
            ->using(WorkspaceMember::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Projects whose team includes this user.
     *
     * @return BelongsToMany<Project, $this, ProjectMember>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_user')->using(ProjectMember::class);
    }

    /**
     * Clients this user belongs to when their workspace role is client.
     *
     * @return BelongsToMany<Client, $this, ClientMember>
     */
    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'client_user')->using(ClientMember::class);
    }

    /**
     * Tasks this user is assigned to.
     *
     * @return BelongsToMany<Task, $this, TaskAssignee>
     */
    public function assignedTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_user')->using(TaskAssignee::class);
    }
}
