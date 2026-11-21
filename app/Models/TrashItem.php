<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Read-only row of the `trash_items` view: anything a user sent to the trash (RN-19).
 *
 * @property string $type
 * @property int $id
 * @property string|null $uuid
 * @property int $workspace_id
 * @property int|null $project_id
 * @property string $title
 * @property Carbon $deleted_at
 * @property int|null $deleted_by_id
 */
class TrashItem extends Model
{
    protected $table = 'trash_items';

    public $timestamps = false;

    public $incrementing = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (): never {
            throw new LogicException('trash_items is a read-only view.');
        });

        static::deleting(function (): never {
            throw new LogicException('trash_items is a read-only view.');
        });
    }

    /**
     * Items of one workspace, most recently deleted first.
     *
     * @param  Builder<TrashItem>  $query
     */
    public function scopeInWorkspace(Builder $query, Workspace $workspace): void
    {
        $query->where('workspace_id', $workspace->id)->orderByDesc('deleted_at');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by_id');
    }
}
