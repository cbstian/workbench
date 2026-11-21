<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * A wiki label applied to a page of the same workspace.
 *
 * `workspace_id` is copied from the Page so the composite foreign keys can check it.
 *
 * @property int $workspace_id
 */
class PageLabelAssignment extends Pivot
{
    protected $table = 'page_wiki_label';

    protected static function booted(): void
    {
        static::creating(function (self $pivot): void {
            $pivot->workspace_id ??= Page::withTrashed()->whereKey($pivot->page_id)->value('workspace_id');
        });
    }
}
