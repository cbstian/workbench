<?php

namespace App\Livewire\Navigation;

use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Projects listed under "Proyectos" in the sidebar (registered as the item's
 * `children` in config/navigation.php).
 *
 * Refreshes itself when a `projects-changed` event is dispatched, so pages that
 * create, rename or delete projects keep the sidebar in sync.
 */
class ProjectLinks extends Component
{
    /**
     * Maximum projects listed; the rest are reachable through "Ver todos".
     */
    public const LIMIT = 30;

    /**
     * Project open in the current page, captured on mount because later
     * Livewire requests do not carry the page route.
     */
    public ?int $activeProjectId = null;

    public function mount(): void
    {
        $routeProject = request()->route('project');

        $this->activeProjectId = $routeProject instanceof Project ? $routeProject->id : null;
    }

    /**
     * Projects ordered by name.
     *
     * @return Collection<int, Project>
     */
    #[Computed]
    public function projects(): Collection
    {
        return Project::query()
            ->orderBy('name')
            ->limit(self::LIMIT + 1)
            ->get(['id', 'uuid', 'name', 'icon']);
    }

    #[On('projects-changed')]
    public function refreshProjects(): void
    {
        unset($this->projects);
    }

    public function render(): View
    {
        return view('livewire.navigation.project-links');
    }
}
