<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Public UUIDv7 next to the internal bigint key (docs/arquitectura/modelo-de-datos.md, P3).
 *
 * URLs and route model binding use the `uuid` column, so sequential ids are
 * never exposed.
 */
trait HasPublicUuid
{
    use HasUuids;

    /**
     * Columns that receive a generated UUID.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * Resolve route bindings by the public UUID.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
