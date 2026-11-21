<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * A task label applied to a task of the same project.
 *
 * `project_id` is copied from the Task so the composite foreign keys can check it.
 *
 * @property int $project_id
 */
class TaskLabelAssignment extends Pivot
{
    protected $table = 'task_task_label';

    protected static function booted(): void
    {
        static::creating(function (self $pivot): void {
            $pivot->project_id ??= Task::withTrashed()->whereKey($pivot->task_id)->value('project_id');
        });
    }
}
