<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Link between a client user and one of their clients (RN-04).
 *
 * `workspace_id` is copied from the Client so the composite foreign keys can check it.
 *
 * @property int $workspace_id
 */
class ClientMember extends Pivot
{
    protected $table = 'client_user';

    protected static function booted(): void
    {
        static::creating(function (self $pivot): void {
            $pivot->workspace_id ??= Client::withTrashed()->whereKey($pivot->client_id)->value('workspace_id');
        });
    }
}
