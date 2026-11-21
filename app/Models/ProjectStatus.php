<?php

namespace App\Models;

use App\Enums\StatusCategory;
use App\Models\Concerns\TracksDeleter;
use Database\Factories\ProjectStatusFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A task status configured on one project (RN-06).
 *
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property StatusCategory $category
 * @property string|null $color
 * @property int $position
 * @property bool $is_initial
 * @property Carbon|null $deleted_at
 * @property int|null $deleted_by_id
 */
#[Fillable(['project_id', 'name', 'category', 'color', 'position', 'is_initial'])]
class ProjectStatus extends Model
{
    /** @use HasFactory<ProjectStatusFactory> */
    use HasFactory, SoftDeletes, TracksDeleter;

    /**
     * Statuses created with a project. Names are Spanish (RN-20).
     * Backlog is the initial status until the project changes it (RN-22).
     *
     * Positions leave gaps of 1024 so a status can be inserted between two others.
     *
     * @var list<array{name: string, category: StatusCategory, color: string, position: int, is_initial: bool}>
     */
    public const array DEFAULTS = [
        ['name' => 'Backlog', 'category' => StatusCategory::Backlog, 'color' => '#9b9a97', 'position' => 1024, 'is_initial' => true],
        ['name' => 'Por hacer', 'category' => StatusCategory::Unstarted, 'color' => '#6b7a90', 'position' => 2048, 'is_initial' => false],
        ['name' => 'En curso', 'category' => StatusCategory::Started, 'color' => '#c98a2b', 'position' => 3072, 'is_initial' => false],
        ['name' => 'Hecho', 'category' => StatusCategory::Completed, 'color' => '#4f8a5b', 'position' => 4096, 'is_initial' => false],
        ['name' => 'Cancelado', 'category' => StatusCategory::Cancelled, 'color' => '#b5544b', 'position' => 5120, 'is_initial' => false],
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => StatusCategory::class,
            'position' => 'integer',
            'is_initial' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
