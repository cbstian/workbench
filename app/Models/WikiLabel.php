<?php

namespace App\Models;

use App\Models\Concerns\TracksDeleter;
use Database\Factories\WikiLabelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A label for wiki pages, scoped to a workspace and distinct from task labels (RN-09).
 *
 * @property int $id
 * @property int $workspace_id
 * @property string $name
 * @property string|null $color
 * @property Carbon|null $deleted_at
 * @property int|null $deleted_by_id
 */
#[Fillable(['workspace_id', 'name', 'color'])]
class WikiLabel extends Model
{
    /** @use HasFactory<WikiLabelFactory> */
    use HasFactory, SoftDeletes, TracksDeleter;

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsToMany<Page, $this, PageLabelAssignment>
     */
    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class, 'page_wiki_label')->using(PageLabelAssignment::class);
    }
}
