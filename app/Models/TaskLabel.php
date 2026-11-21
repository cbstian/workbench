<?php

namespace App\Models;

use App\Models\Concerns\TracksDeleter;
use Database\Factories\TaskLabelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A label for tasks of one project. There are no default labels (RN-06).
 *
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property string|null $color
 * @property Carbon|null $deleted_at
 * @property int|null $deleted_by_id
 */
#[Fillable(['project_id', 'name', 'color'])]
class TaskLabel extends Model
{
    /** @use HasFactory<TaskLabelFactory> */
    use HasFactory, SoftDeletes, TracksDeleter;

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsToMany<Task, $this, TaskLabelAssignment>
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_task_label')->using(TaskLabelAssignment::class);
    }
}
