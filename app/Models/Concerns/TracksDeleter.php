<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Records who sent a soft-deleted model to the trash (RN-19) and clears it on restore.
 *
 * Use it together with SoftDeletes on tables that have a `deleted_by_id` column.
 */
trait TracksDeleter
{
    public static function bootTracksDeleter(): void
    {
        static::softDeleted(function (Model $model): void {
            $deleterId = Auth::id();

            if ($deleterId === null) {
                return;
            }

            $model->newQueryWithoutScopes()
                ->whereKey($model->getKey())
                ->update(['deleted_by_id' => $deleterId]);

            $model->setAttribute('deleted_by_id', $deleterId);
            $model->syncOriginalAttribute('deleted_by_id');
        });

        static::restoring(function (Model $model): void {
            $model->setAttribute('deleted_by_id', null);
        });
    }
}
