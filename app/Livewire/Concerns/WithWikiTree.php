<?php

namespace App\Livewire\Concerns;

use App\Models\Page;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

/**
 * Page tree of one wiki (workspace or project) for the wiki sidebar and the
 * parent pickers. Pages under a trashed page are left out with it.
 */
trait WithWikiTree
{
    abstract protected function wikiWorkspace(): Workspace;

    abstract protected function wikiProject(): ?Project;

    /**
     * Nested nodes: each one has the page and its children.
     *
     * @return list<array{page: Page, children: list<mixed>}>
     */
    #[Computed]
    public function tree(): array
    {
        $byParent = Page::query()
            ->inWiki($this->wikiWorkspace(), $this->wikiProject())
            ->orderBy('position')
            ->orderBy('id')
            ->get(['id', 'uuid', 'parent_id', 'title', 'icon', 'position'])
            ->groupBy(fn (Page $page): string => (string) ($page->parent_id ?? 'root'));

        $build = function (string $parentKey) use (&$build, $byParent): array {
            return ($byParent[$parentKey] ?? collect())
                ->map(fn (Page $page): array => ['page' => $page, 'children' => $build((string) $page->id)])
                ->values()
                ->all();
        };

        return $build('root');
    }

    /**
     * Flat list for a select: «— Subpágina» with its depth, skipping a page
     * and its descendants (a page cannot move under itself).
     *
     * @return list<array{label: string, value: int}>
     */
    public function parentOptions(?int $excludeId = null): array
    {
        $options = [];

        $walk = function (array $nodes, int $depth) use (&$walk, &$options, $excludeId): void {
            foreach ($nodes as $node) {
                if ($node['page']->id === $excludeId) {
                    continue;
                }

                $options[] = [
                    'label' => str_repeat('— ', $depth).trim(($node['page']->icon ?? '').' '.$node['page']->title),
                    'value' => $node['page']->id,
                ];

                $walk($node['children'], $depth + 1);
            }
        };

        $walk($this->tree, 0);

        return $options;
    }

    /**
     * Ids of the pages in the tree (active and reachable).
     *
     * @return Collection<int, int>
     */
    protected function treePageIds(): Collection
    {
        $ids = collect();

        $walk = function (array $nodes) use (&$walk, $ids): void {
            foreach ($nodes as $node) {
                $ids->push($node['page']->id);
                $walk($node['children']);
            }
        };

        $walk($this->tree);

        return $ids;
    }

    #[On('wiki-changed')]
    public function refreshTree(): void
    {
        unset($this->tree);
    }
}
