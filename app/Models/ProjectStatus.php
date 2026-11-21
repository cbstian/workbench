<?php

namespace App\Models;

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
 * @property int $position
 * @property bool $is_initial
 * @property Carbon|null $deleted_at
 */
#[Fillable(['project_id', 'name', 'position', 'is_initial'])]
class ProjectStatus extends Model
{
    /** @use HasFactory<ProjectStatusFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Statuses created with a project. Names are Spanish (RN-20).
     * Backlog is the initial status until the project changes it (RN-22).
     *
     * @var list<array{name: string, position: int, is_initial: bool}>
     */
    public const array DEFAULTS = [
        ['name' => 'Backlog', 'position' => 0, 'is_initial' => true],
        ['name' => 'Por hacer', 'position' => 1, 'is_initial' => false],
        ['name' => 'En curso', 'position' => 2, 'is_initial' => false],
        ['name' => 'Hecho', 'position' => 3, 'is_initial' => false],
        ['name' => 'Cancelado', 'position' => 4, 'is_initial' => false],
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
