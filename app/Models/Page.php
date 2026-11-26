<?php

namespace App\Models;

use App\Enums\CommentThread;
use App\Enums\PageActivityEvent;
use App\Models\Concerns\Commentable;
use App\Models\Concerns\HasComments;
use App\Models\Concerns\HasPublicUuid;
use App\Models\Concerns\InheritsWorkspace;
use App\Models\Concerns\TracksDeleter;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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
class Page extends Model implements Commentable
{
    /** @use HasFactory<PageFactory> */
    use HasComments, HasFactory, HasPublicUuid, InheritsWorkspace, SoftDeletes, TracksDeleter;

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
     * Whether the page is reachable: its project and every ancestor are
     * active. Children of a trashed page stay hidden without being marked
     * (docs/arquitectura/modelo-de-datos.md, 7).
     */
    public function isInActiveTree(): bool
    {
        if ($this->trashed() || ($this->project_id !== null && $this->project === null) || $this->workspace === null) {
            return false;
        }

        $parentId = $this->parent_id;

        while ($parentId !== null) {
            $parent = self::withTrashed()->select(['id', 'parent_id', 'deleted_at'])->find($parentId);

            if ($parent === null || $parent->trashed()) {
                return false;
            }

            $parentId = $parent->parent_id;
        }

        return true;
    }

    /**
     * Pages of one wiki: the workspace wiki when the project is null.
     *
     * @param  Builder<Page>  $query
     */
    public function scopeInWiki(Builder $query, Workspace $workspace, ?Project $project): void
    {
        $query->where('workspace_id', $workspace->id)
            ->when($project, fn (Builder $query) => $query->where('project_id', $project->id), fn (Builder $query) => $query->whereNull('project_id'));
    }

    /**
     * The body rendered from Markdown. Raw HTML is escaped and unsafe links
     * dropped, so stored content cannot inject markup.
     */
    public function bodyHtml(): string
    {
        return filled($this->body)
            ? Str::markdown($this->body, ['html_input' => 'escape', 'allow_unsafe_links' => false])
            : '';
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    /**
     * @return HasMany<PageComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(PageComment::class);
    }

    protected function followersTable(): string
    {
        return 'page_followers';
    }

    /**
     * Whoever can read the thread: the project team with wiki access, or only
     * the super admin for the workspace wiki (RN-24). The client thread adds
     * the users of the project's client, or every client user of the
     * workspace for a workspace page.
     *
     * @return Collection<int, User>
     */
    public function mentionableUsers(CommentThread $thread = CommentThread::Internal): Collection
    {
        $clientUsers = match (true) {
            $thread !== CommentThread::Client => [],
            $this->project_id !== null => $this->project->client?->users()->get() ?? [],
            default => User::query()->whereHas('clients', fn (Builder $query) => $query->where('clients.workspace_id', $this->workspace_id))->get(),
        };

        return $this->filterMentionable([
            ...($this->project?->members()->get() ?? []),
            $this->accountOwner($this->workspace_id),
            ...$clientUsers,
        ], $thread);
    }

    /**
     * The creator follows the page by default.
     *
     * @return list<int>
     */
    public function implicitFollowerIds(): array
    {
        return [$this->created_by_id];
    }

    public function commentableType(): string
    {
        return 'page';
    }

    public function commentableTitle(): string
    {
        return $this->title;
    }

    public function commentableContext(): string
    {
        return $this->project?->name ?? 'Wiki del espacio';
    }

    public function commentableUrl(): string
    {
        return route('pages.show', $this);
    }

    public function recordCommentActivity(User $actor, Model $comment, bool $isDeletion = false): void
    {
        PageActivity::record($this, $actor, $isDeletion ? PageActivityEvent::CommentDeleted : PageActivityEvent::CommentAdded, metadata: ['comment_id' => $comment->getKey()]);
    }
}
