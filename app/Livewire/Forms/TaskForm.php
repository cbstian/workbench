<?php

namespace App\Livewire\Forms;

use App\Actions\Tasks\CreateTask;
use App\Actions\Tasks\UpdateTask;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Data of a task or subtask (F-06, RN-07): name, description, status,
 * priority, dates, assignees from the project team and project labels.
 */
class TaskForm extends Form
{
    public ?Project $project = null;

    public ?Task $task = null;

    public string $name = '';

    public ?string $description = null;

    public ?int $status_id = null;

    public ?int $priority_id = null;

    public ?string $starts_on = null;

    public ?string $ends_on = null;

    /**
     * @var list<int>
     */
    public array $assignee_ids = [];

    /**
     * @var list<int>
     */
    public array $label_ids = [];

    /**
     * Start a new task with the project's initial status and priority (RN-22).
     */
    public function setProject(Project $project): void
    {
        $this->reset();
        $this->project = $project;
        $this->status_id = $project->initialStatus()->value('id');
        $this->priority_id = $project->initialPriority()->value('id');
    }

    public function setTask(Task $task): void
    {
        $this->reset();
        $this->task = $task;
        $this->project = $task->project;
        $this->name = $task->name;
        $this->description = $task->description;
        $this->status_id = $task->status_id;
        $this->priority_id = $task->priority_id;
        $this->starts_on = $task->starts_on?->format('Y-m-d');
        $this->ends_on = $task->ends_on?->format('Y-m-d');
        $this->assignee_ids = $task->assignees()->pluck('users.id')->map(fn ($id): int => (int) $id)->all();
        $this->label_ids = $task->labels()->pluck('task_labels.id')->map(fn ($id): int => (int) $id)->all();
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function rules(): array
    {
        $projectId = $this->project?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'status_id' => ['required', 'integer', Rule::exists('project_statuses', 'id')->where('project_id', $projectId)->whereNull('deleted_at')],
            'priority_id' => ['required', 'integer', Rule::exists('project_priorities', 'id')->where('project_id', $projectId)->whereNull('deleted_at')],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'assignee_ids' => ['array'],
            'assignee_ids.*' => ['integer', Rule::exists('project_user', 'user_id')->where('project_id', $projectId)],
            'label_ids' => ['array'],
            'label_ids.*' => ['integer', Rule::exists('task_labels', 'id')->where('project_id', $projectId)->whereNull('deleted_at')],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'status_id' => 'estado',
            'priority_id' => 'prioridad',
            'starts_on' => 'fecha de inicio',
            'ends_on' => 'fecha de término',
            'assignee_ids' => 'asignados',
            'assignee_ids.*' => 'asignado',
            'label_ids' => 'etiquetas',
            'label_ids.*' => 'etiqueta',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'ends_on.after_or_equal' => 'La fecha de término no puede ser anterior a la de inicio.',
            'assignee_ids.*.exists' => 'Solo puedes asignar integrantes del equipo del proyecto.',
        ];
    }

    public function store(User $actor, ?Task $parent = null): Task
    {
        $validated = $this->validate();

        $task = app(CreateTask::class)->handle(
            $this->project,
            $actor,
            $this->taskAttributes($validated),
            $this->ids($validated['assignee_ids']),
            $this->ids($validated['label_ids']),
            $parent,
        );

        $this->setProject($this->project);

        return $task;
    }

    public function update(User $actor): void
    {
        $validated = $this->validate();

        app(UpdateTask::class)->handle(
            $this->task,
            $actor,
            $this->taskAttributes($validated),
            $this->ids($validated['assignee_ids']),
            $this->ids($validated['label_ids']),
        );

        $this->setTask($this->task->fresh());
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{name: string, description: string|null, status_id: int, priority_id: int, starts_on: string|null, ends_on: string|null}
     */
    private function taskAttributes(array $validated): array
    {
        return [
            'name' => trim($validated['name']),
            'description' => filled($validated['description']) ? $validated['description'] : null,
            'status_id' => (int) $validated['status_id'],
            'priority_id' => (int) $validated['priority_id'],
            'starts_on' => $validated['starts_on'] ?: null,
            'ends_on' => $validated['ends_on'] ?: null,
        ];
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return list<int>
     */
    private function ids(array $ids): array
    {
        return array_values(array_unique(array_map('intval', $ids)));
    }
}
