<?php

namespace App\Livewire\Wiki;

use App\Actions\Wiki\CreatePage;
use App\Livewire\Concerns\WithWikiTree;
use App\Models\Page;
use App\Models\Project;
use App\Models\WikiLabel;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Home of a wiki (F-09): the workspace wiki (/wiki, super admin only, RN-24)
 * or a project wiki (/projects/{project}/wiki, admins and members). Shows the
 * page tree, finds pages by text or wiki label and creates pages.
 */
class Index extends Component
{
    use WithWikiTree;

    #[Locked]
    public ?Project $project = null;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: null)]
    public ?int $label = null;

    public bool $showForm = false;

    public string $title = '';

    public ?string $icon = '📄';

    public ?int $parentId = null;

    public function mount(?Project $project = null): void
    {
        $this->project = $project?->exists ? $project : null;

        if ($this->project !== null) {
            $this->authorize('viewProjectWiki', [Page::class, $this->project]);
            app(CurrentWorkspace::class)->switchTo($this->project->workspace);
        } else {
            $this->authorize('viewWorkspaceWiki', [Page::class, $this->wikiWorkspace()]);
        }
    }

    protected function wikiWorkspace(): Workspace
    {
        $workspace = $this->project?->workspace ?? app(CurrentWorkspace::class)->get();
        abort_if($workspace === null, 404);

        return $workspace;
    }

    protected function wikiProject(): ?Project
    {
        return $this->project;
    }

    /**
     * Pages matching the search or the label, among the reachable ones.
     *
     * @return Collection<int, Page>|null
     */
    #[Computed]
    public function results(): ?Collection
    {
        if ($this->search === '' && $this->label === null) {
            return null;
        }

        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $this->search).'%';

        return Page::query()
            ->whereKey($this->treePageIds()->all())
            ->when($this->search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->whereRaw("search_vector @@ plainto_tsquery('public.es_unaccent', ?)", [$this->search])
                ->orWhereLike('title', $like)))
            ->when($this->label !== null, fn (Builder $query) => $query->whereHas('labels', fn (Builder $query) => $query->whereKey($this->label)))
            ->with('labels:id,name,color')
            ->orderBy('title')
            ->limit(50)
            ->get();
    }

    /**
     * Wiki labels of the workspace in use in this wiki.
     *
     * @return Collection<int, WikiLabel>
     */
    #[Computed]
    public function labels(): Collection
    {
        $pageIds = $this->treePageIds()->all();

        return $this->wikiWorkspace()->wikiLabels()
            ->whereHas('pages', fn (Builder $query) => $query->whereKey($pageIds))
            ->orderBy('name')
            ->get();
    }

    public function filterByLabel(?int $labelId): void
    {
        $this->label = $this->label === $labelId ? null : $labelId;
    }

    public function create(?int $parentId = null): void
    {
        $this->reset('title', 'icon');
        $this->resetValidation();
        $this->parentId = $parentId;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->project !== null
            ? $this->authorize('viewProjectWiki', [Page::class, $this->project])
            : $this->authorize('viewWorkspaceWiki', [Page::class, $this->wikiWorkspace()]);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:32'],
            'parentId' => ['nullable', 'integer', 'in:'.$this->treePageIds()->implode(',')],
        ], attributes: ['title' => 'título', 'icon' => 'icono', 'parentId' => 'página padre']);

        $page = app(CreatePage::class)->handle(
            $this->wikiWorkspace(),
            $this->project,
            auth()->user(),
            ['title' => trim($validated['title']), 'icon' => $validated['icon'] ?: null],
            $validated['parentId'] ? Page::query()->findOrFail($validated['parentId']) : null,
        );

        $this->redirectRoute('pages.show', ['page' => $page, 'edit' => 1], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.wiki.index')->title($this->project ? 'Wiki · '.$this->project->name : 'Wiki del espacio');
    }
}
