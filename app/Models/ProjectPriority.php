<?php

namespace App\Models;

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
 * @property int $position
 * @property bool $is_initial
 * @property Carbon|null $deleted_at
 */
#[Fillable(['project_id', 'name', 'position', 'is_initial'])]
class ProjectPriority extends Model
{
    /** @use HasFactory<ProjectPriorityFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Priorities created with a project. "ninguna" is the initial priority (RN-22).
     *
     * @var list<array{name: string, position: int, is_initial: bool}>
     */
    public const array DEFAULTS = [
        ['name' => 'ninguna', 'position' => 0, 'is_initial' => true],
        ['name' => 'baja', 'position' => 1, 'is_initial' => false],
        ['name' => 'media', 'position' => 2, 'is_initial' => false],
        ['name' => 'alta', 'position' => 3, 'is_initial' => false],
        ['name' => 'urgente', 'position' => 4, 'is_initial' => false],
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
