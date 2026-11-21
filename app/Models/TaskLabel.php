<?php

namespace App\Models;

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
 * @property Carbon|null $deleted_at
 */
#[Fillable(['project_id', 'name'])]
class TaskLabel extends Model
{
    /** @use HasFactory<TaskLabelFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsToMany<Task, $this>
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class);
    }
}
