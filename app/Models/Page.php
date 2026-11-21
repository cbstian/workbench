<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use App\Models\Concerns\InheritsWorkspace;
use App\Models\Concerns\TracksDeleter;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A wiki page. Without a project it belongs to the workspace wiki (RN-09).
 *
 * @property int $id
 * @property string $uuid
 * @property int $workspace_id
 * @property int|null $project_id
 * @property int|null $parent_id
 * @property string $title
 * @property string|null $icon
 * @property string|null $body
 * @property bool $is_visible_to_client
 * @property int $position
 * @property int $created_by_id
 * @property int|null $updated_by_id
 * @property Carbon|null $deleted_at
 * @property int|null $deleted_by_id
 */
#[Fillable([
    'workspace_id',
    'project_id',
    'parent_id',
    'title',
    'icon',
    'body',
    'is_visible_to_client',
    'position',
    'created_by_id',
    'updated_by_id',
])]
#[Hidden(['search_vector'])]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory, HasPublicUuid, InheritsWorkspace, SoftDeletes, TracksDeleter;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_visible_to_client' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * A project page takes the workspace of its project.
     */
    protected function inheritedWorkspaceId(): ?int
    {
        return Project::withTrashed()->whereKey($this->project_id)->value('workspace_id');
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }

    /**
     * @return HasMany<Page, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Page::class, 'parent_id')->orderBy('position');
    }

    /**
     * @return BelongsToMany<WikiLabel, $this, PageLabelAssignment>
     */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(WikiLabel::class, 'page_wiki_label')->using(PageLabelAssignment::class);
    }

    /**
     * @return HasMany<PageFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(PageFile::class);
    }

    /**
     * @return HasMany<PageActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(PageActivity::class);
    }
}
