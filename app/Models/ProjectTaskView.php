<?php

namespace App\Models;

use App\Enums\TaskView;
use Database\Factories\ProjectTaskViewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The task view and filters a user last left active on a project (RN-11).
 *
 * @property int $id
 * @property int $user_id
 * @property int $project_id
 * @property TaskView $view
 * @property array{
 *     status_ids: list<int>,
 *     priority_ids: list<int>,
 *     assignee_ids: list<int>,
 *     label_ids: list<int>,
 *     starts_from: string|null,
 *     starts_to: string|null,
 *     ends_from: string|null,
 *     ends_to: string|null
 * } $filters
 */
#[Fillable(['user_id', 'project_id', 'view', 'filters'])]
class ProjectTaskView extends Model
{
    /** @use HasFactory<ProjectTaskViewFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'view' => TaskView::class,
            'filters' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
