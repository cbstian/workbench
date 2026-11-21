<?php

namespace App\Livewire\Tasks;

use App\Actions\Tasks\CreateTask;
use App\Actions\Tasks\DeleteTask;
use App\Livewire\Concerns\WithTaskOptions;
use App\Livewire\Forms\TaskForm;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Support\CurrentWorkspace;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

/**
 * Task page (F-06, F-07): its data, subtasks of one level and history.
 *
 * Everybody on the project team sees it; only users who may edit it (admins,
 * or a member assigned to it or to its parent) change it (RN-23, A-17).
 */
class Show extends Component
{
    use Interactions, WithTaskOptions;

    public Task $task;

    public TaskForm $form;

    public string $newSubtaskName = '';

    public function mount(): void
    {
        $this->authorize('view', $this->task);

        $currentWorkspace = app(CurrentWorkspace::class);

        if ($currentWorkspace->get()?->id !== $this->task->workspace_id) {
            $currentWorkspace->switchTo($this->task->project->workspace);
        }

        $this->form->setTask($this->task);
    }

    protected function taskOptionsProject(): Project
    {
        return $this->task->project;
    }

    #[Computed]
    public function canEdit(): bool
    {
        return auth()->user()->can('update', $this->task);
    }

    #[Computed]
    public function canAddSubtasks(): bool
    {
        return auth()->user()->can('addSubtask', $this->task);
    }

    /**
     * @return Collection<int, Task>
     */
    #[Computed]
    public function subtasks(): Collection
    {
        return $this->task->subtasks()->with(['status', 'assignees:id,name'])->get();
    }

    /**
     * History, newest first (RN-08).
     *
     * @return Collection<int, TaskActivity>
     */
    #[Computed]
    public function activities(): Collection
    {
        return $this->task->activities()->with('user:id,name')->latest('created_at')->latest('id')->limit(100)->get();
    }

    /**
     * Comments and attachments change the history (child components).
     */
    #[On('task-activity-changed')]
    public function refreshActivities(): void
    {
        unset($this->activities);
    }

    public function save(): void
    {
        $this->authorize('update', $this->task);

        $this->form->update(auth()->user());

        $this->task->refresh();
        unset($this->activities);
        $this->toast()->success('Tarea guardada')->send();
    }

    public function addSubtask(): void
    {
        $this->authorize('addSubtask', $this->task);

        $validated = $this->validate(
            ['newSubtaskName' => ['required', 'string', 'max:255']],
            attributes: ['newSubtaskName' => 'nombre de la subtarea'],
        );

        app(CreateTask::class)->handle($this->task->project, auth()->user(), ['name' => trim($validated['newSubtaskName'])], parent: $this->task);

        $this->newSubtaskName = '';
        unset($this->subtasks, $this->activities);
    }

    public function confirmDelete(): void
    {
        $this->dialog()
            ->question("¿Enviar «{$this->task->name}» a la papelera?", $this->task->parent_id === null
                ? 'Sus subtareas se ocultan con ella. Puedes restaurarla desde la papelera.'
                : 'Puedes restaurarla desde la papelera.')
            ->confirm('Enviar a la papelera', 'delete')
            ->cancel('Cancelar')
            ->send();
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->task);

        $parent = $this->task->parent;
        app(DeleteTask::class)->handle($this->task, auth()->user());

        $this->toast()->success('Tarea enviada a la papelera')->flash()->send();

        $parent !== null
            ? $this->redirectRoute('tasks.show', $parent, navigate: true)
            : $this->redirectRoute('projects.show', $this->task->project, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.tasks.show')->title($this->task->name);
    }
}
