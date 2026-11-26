<?php

namespace App\Livewire\Portal;

use App\Models\Page;
use App\Support\ClientPortal;
use App\Support\CurrentWorkspace;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * A wiki page in the client portal (P-14): read only, with the visible
 * pages of the same wiki beside it and the conversation with the client.
 * Its files are not shown.
 */
class PageShow extends Component
{
    public Page $page;

    public function mount(): void
    {
        $this->authorize('viewInPortal', $this->page);

        $currentWorkspace = app(CurrentWorkspace::class);

        if ($currentWorkspace->get()?->id !== $this->page->workspace_id) {
            $currentWorkspace->switchTo($this->page->workspace);
        }

        $this->page->loadMissing(['project.client', 'updater:id,name']);
    }

    /**
     * Visible pages of the same wiki, in tree order.
     *
     * @return Collection<int, Page>
     */
    #[Computed]
    public function tree(): Collection
    {
        return app(ClientPortal::class)->visiblePages($this->page->workspace, $this->page->project);
    }

    /**
     * Ancestors of the page, root first. All of them are visible, or the
     * page would not be.
     *
     * @return Collection<int, Page>
     */
    #[Computed]
    public function ancestors(): Collection
    {
        $byId = $this->tree->keyBy('id');
        $ancestors = new Collection;
        $parentId = $this->page->parent_id;

        while ($parentId !== null && $byId->has($parentId)) {
            $ancestors->prepend($byId->get($parentId));
            $parentId = $byId->get($parentId)->parent_id;
        }

        return $ancestors;
    }

    /**
     * Visible subpages of the page.
     *
     * @return Collection<int, Page>
     */
    #[Computed]
    public function children(): Collection
    {
        return $this->tree->where('parent_id', $this->page->id)->values();
    }

    public function render(): View
    {
        return view('livewire.portal.page-show')->title($this->page->title);
    }
}
