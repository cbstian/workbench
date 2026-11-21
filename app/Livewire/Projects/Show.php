<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Support\CurrentWorkspace;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Project page: header with icon, name and description, its team and its
 * workflow. Tasks, views and the project wiki will hang from here.
 *
 * Opening a project of another visible workspace switches to that workspace.
 */
class Show extends Component
{
    public Project $project;

    public function mount(): void
    {
        $this->authorize('view', $this->project);

        $currentWorkspace = app(CurrentWorkspace::class);

        if ($currentWorkspace->get()?->id !== $this->project->workspace_id) {
            $currentWorkspace->switchTo($this->project->workspace);
        }

        $this->project->load(['client', 'members', 'statuses', 'priorities', 'taskLabels']);
    }

    public function render(): View
    {
        return view('livewire.projects.show')->title($this->project->name);
    }
}
