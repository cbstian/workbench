<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A project a user hid on their own copy of the shared board (E11).
 *
 * Hiding does not change the board for anyone else. There is no updated_at.
 *
 * @property int $user_id
 * @property int $project_id
 */
#[Fillable(['user_id', 'project_id'])]
class BoardProjectHide extends Model
{
    public const UPDATED_AT = null;

    /**
     * The table's key is (user_id, project_id). There is no id column.
     */
    public $incrementing = false;

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
