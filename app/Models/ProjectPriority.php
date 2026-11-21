<?php

namespace App\Models;

use App\Models\Concerns\TracksDeleter;
use Database\Factories\ProjectPriorityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A task priority configured on one project (RN-16).
 *
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property string|null $color
 * @property int $position
 * @property bool $is_initial
 * @property Carbon|null $deleted_at
 * @property int|null $deleted_by_id
 */
#[Fillable(['project_id', 'name', 'color', 'position', 'is_initial'])]
class ProjectPriority extends Model
{
    /** @use HasFactory<ProjectPriorityFactory> */
    use HasFactory, SoftDeletes, TracksDeleter;

    /**
     * Priorities created with a project. "ninguna" is the initial priority (RN-22).
     *
     * A higher position means more urgent.
     *
     * @var list<array{name: string, color: string, position: int, is_initial: bool}>
     */
    public const array DEFAULTS = [
        ['name' => 'ninguna', 'color' => '#9b9a97', 'position' => 1024, 'is_initial' => true],
        ['name' => 'baja', 'color' => '#6b7a90', 'position' => 2048, 'is_initial' => false],
        ['name' => 'media', 'color' => '#c98a2b', 'position' => 3072, 'is_initial' => false],
        ['name' => 'alta', 'color' => '#d0702f', 'position' => 4096, 'is_initial' => false],
        ['name' => 'urgente', 'color' => '#b5544b', 'position' => 5120, 'is_initial' => false],
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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
