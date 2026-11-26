<?php

namespace App\Livewire\Navigation;

use App\Models\Project;
use App\Support\CurrentWorkspace;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Sidebar sub-list of «Mis proyectos» for a client user: the projects of
 * their clients shared with them, by name.
 */
class PortalProjectLinks extends Component
{
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
     * @return Collection<int, Project>
     */
    #[Computed]
    public function projects(): Collection
    {
        $workspace = app(CurrentWorkspace::class)->get();

        if ($workspace === null) {
            return new Collection;
        }

        return Project::query()
            ->visibleToClient(auth()->user(), $workspace)
            ->orderBy('name')
            ->get(['id', 'uuid', 'name', 'icon']);
    }

    #[On('workspaces-changed')]
    public function refreshProjects(): void
    {
        unset($this->projects);
    }

    public function render(): View
    {
        return view('livewire.navigation.portal-project-links');
    }
}
