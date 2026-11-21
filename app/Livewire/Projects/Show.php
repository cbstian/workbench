<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Project page: header with icon, name and description. Tasks, views and the
 * project wiki will hang from here.
 *
 * Logic lives here; markup lives in resources/views/livewire/projects/show.blade.php.
 */
class Show extends Component
{
    public Project $project;

    public function render(): View
    {
        return view('livewire.projects.show')->title($this->project->name);
    }
}
