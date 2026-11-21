<?php

namespace App\Models;

use App\Enums\WorkspaceRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Invitation of a user to a workspace, with a single role.
 *
 * @property int $workspace_id
 * @property int $user_id
 * @property WorkspaceRole $role
 */
class WorkspaceMember extends Pivot
{
    protected $table = 'workspace_user';

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
