<?php

namespace App\Models;

use App\Enums\WorkspaceRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Membership of a user in a workspace with a single role. It is the only
 * source of truth for the workspace role (docs/arquitectura/modelo-de-datos.md, D-04).
 *
 * The spatie role of the user in the workspace (team) follows this row: it is
 * synced when the membership is created or its role changes, and removed when
 * the membership is deleted. Attach, detach and updateExistingPivot go through
 * this class because the relations declare ->using(WorkspaceMember::class).
 *
 * @property int $workspace_id
 * @property int $user_id
 * @property WorkspaceRole $role
 * @property int|null $created_by_id
 */
class WorkspaceMember extends Pivot
{
    protected $table = 'workspace_user';

    protected static function booted(): void
    {
        static::saved(function (self $membership): void {
            $membership->syncSpatieRole([$membership->role instanceof WorkspaceRole ? $membership->role->value : (string) $membership->role]);
        });

        static::deleted(function (self $membership): void {
            $membership->syncSpatieRole([]);
        });
    }

    /**
     * Replace the user's spatie roles in this workspace.
     *
     * @param  list<string>  $roles
     */
    private function syncSpatieRole(array $roles): void
    {
        $user = User::withTrashed()->find($this->user_id);

        $user?->withinWorkspaceTeam((int) $this->workspace_id, fn () => $user->syncRoles($roles));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => WorkspaceRole::class,
        ];
    }
}
