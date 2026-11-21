<?php

namespace App\Models;

use App\Enums\WorkspaceRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Membership of a user in a workspace with a single role. It is the only
 * source of truth for the workspace role (docs/arquitectura/modelo-de-datos.md, D-04).
 *
 * @property int $workspace_id
 * @property int $user_id
 * @property WorkspaceRole $role
 * @property int|null $created_by_id
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
