<?php

namespace App\Models;

use App\Enums\PageActivityEvent;
use App\Models\Concerns\InheritsWorkspace;
use Database\Factories\PageActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One recorded change of a wiki page. Pages are not versioned (RN-09).
 *
 * Append-only: the database rejects updates and deletes. `changes` holds
 * `{"field": {"old": ..., "new": ...}}` for the fields that changed.
 *
 * @property int $id
 * @property int $workspace_id
 * @property int $page_id
 * @property int|null $user_id
 * @property PageActivityEvent $event
 * @property array<string, array{old: mixed, new: mixed}>|null $changes
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 */
#[Fillable(['page_id', 'user_id', 'event', 'changes', 'metadata'])]
class PageActivity extends Model
{
    /** @use HasFactory<PageActivityFactory> */
    use HasFactory, InheritsWorkspace;

    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event' => PageActivityEvent::class,
            'changes' => 'array',
            'metadata' => 'array',
        ];
    }

    protected function inheritedWorkspaceId(): ?int
    {
        return Page::withTrashed()->whereKey($this->page_id)->value('workspace_id');
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * User who made the change; null for console actions.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
