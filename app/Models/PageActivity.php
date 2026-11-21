<?php

namespace App\Models;

use Database\Factories\PageActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One recorded change of a wiki page. Pages are not versioned (RN-09).
 *
 * @property int $id
 * @property int $page_id
 * @property int $user_id
 * @property string $event
 * @property array<string, mixed>|null $properties
 * @property Carbon|null $created_at
 */
#[Fillable(['page_id', 'user_id', 'event', 'properties'])]
class PageActivity extends Model
{
    /** @use HasFactory<PageActivityFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
