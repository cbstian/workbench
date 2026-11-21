<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Membership of an admin or member in a project team (RN-21). Never a client user.
 *
 * `workspace_id` is copied from the Project so the composite foreign keys can check it.
 *
 * @property int $workspace_id
 */
class ProjectMember extends Pivot
{
    protected $table = 'project_user';

    protected static function booted(): void
    {
        static::creating(function (self $pivot): void {
            $pivot->workspace_id ??= Project::withTrashed()->whereKey($pivot->project_id)->value('workspace_id');
        });
    }
}
