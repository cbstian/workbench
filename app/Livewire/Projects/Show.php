<?php

namespace App\Livewire\Projects;

use App\Livewire\Concerns\WithTaskOptions;
use App\Livewire\Forms\TaskForm;
use App\Models\Project;
use App\Models\Task;
use App\Support\CurrentWorkspace;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

/**
 * Project page: header, team and workflow, and its tasks listed by status
 * (F-06). Admins and members see every task of their projects (RN-23).
 *
 * Opening a project of another visible workspace switches to that workspace.
 */
class Show extends Component
{
    use Interactions, WithTaskOptions;

    public Project $project;

    public TaskForm $form;

    public bool $showTaskForm = false;

    public function mount(): void
    {
        $this->authorize('view', $this->project);

        $currentWorkspace = app(CurrentWorkspace::class);

        if ($currentWorkspace->get()?->id !== $this->project->workspace_id) {
            $currentWorkspace->switchTo($this->project->workspace);
        }

        $this->project->load(['client', 'members', 'statuses', 'priorities']);
    }

    protected function taskOptionsProject(): Project
    {
        return $this->project;
    }

    /**
     * Root tasks grouped by status, in the order of the project's statuses.
     *
     * @return Collection<int, Task>
     */
    #[Computed]
    public function tasks(): Collection
    {
        return $this->project->rootTasks()
            ->with(['priority', 'assignees:id,name', 'labels:id,name,color'])
            ->withCount(['subtasks', 'subtasks as closed_subtasks_count' => fn ($query) => $query->whereNotNull('completed_at')])
            ->orderBy('position')
            ->get();
    }

    #[Computed]
    public function canCreateTasks(): bool
    {
        return auth()->user()->can('create', [Task::class, $this->project]);
    }

    /**
     * Open the new task form. A team member starts as its assignee, so a
     * member can keep editing what they create (RN-23).
     */
    public function createTask(): void
    {
        $this->authorize('create', [Task::class, $this->project]);

        $this->form->setProject($this->project);
        $this->form->resetValidation();

        if (auth()->user()->isOnTeamOf($this->project)) {
            $this->form->assignee_ids = [auth()->id()];
        }

        $this->showTaskForm = true;
    }

    public function saveTask(): void
    {
        $this->authorize('create', [Task::class, $this->project]);

        $task = $this->form->store(auth()->user());

        $this->showTaskForm = false;
        unset($this->tasks);
        $this->toast()->success('Tarea creada', "«{$task->name}» quedó en {$task->status->name}.")->send();
    }

    public function render(): View
    {
        return view('livewire.projects.show')->title($this->project->name);
    }
}
