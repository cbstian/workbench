<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Shared behaviour of task and page comments: one-level threads, mentions
 * stored as `@[user-uuid]` tokens in the body, author.
 *
 * @mixin Model
 */
trait IsComment
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
        ];
    }

    /**
     * Task or page commented on.
     */
    abstract public function commentable(): Commentable;

    /**
     * Name of the pivot table of mentions.
     */
    abstract protected function mentionsTable(): string;

    /**
     * Root comment answered, when this is a reply.
     *
     * @return BelongsTo<static, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    /**
     * @return HasMany<static, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id')->oldest();
    }

    /**
     * Users mentioned in the body.
     *
     * @return BelongsToMany<User, $this>
     */
    public function mentions(): BelongsToMany
    {
        return $this->belongsToMany(User::class, $this->mentionsTable());
    }

    /**
     * Author of the comment.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isReply(): bool
    {
        return $this->getAttribute('parent_id') !== null;
    }
}
