<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Assignment of a project team member to a task (RN-18). Never a client user.
 *
 * `project_id` is copied from the Task so the composite foreign keys can check it.
 *
 * @property int $project_id
 */
class TaskAssignee extends Pivot
{
    protected $table = 'task_user';

    protected static function booted(): void
    {
        static::creating(function (self $pivot): void {
            $pivot->project_id ??= Task::withTrashed()->whereKey($pivot->task_id)->value('project_id');
        });
    }
}
