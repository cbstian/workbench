<?php

namespace App\Models;

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
 * @property Carbon|null $deactivated_at
 * @property int|null $deactivated_by_id
 * @property Carbon|null $deleted_at
 * @property int|null $deleted_by_id
 */
#[Fillable(['name', 'email', 'password', 'deactivated_at'])]
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
        return $this->ownedAccount()->exists();
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
