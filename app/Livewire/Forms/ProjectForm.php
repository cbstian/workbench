<?php

namespace App\Livewire\Forms;

use App\Actions\Projects\CreateProject;
use App\Actions\Projects\SetProjectPause;
use App\Enums\ProjectPriorityLevel;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Name, description, icon, client and client visibility of a project
 * (F-05, RN-13). A new project may start with its team.
 */
class ProjectForm extends Form
{
    public ?Project $project = null;

    public ?Workspace $workspace = null;

    public string $name = '';

    public ?string $description = null;

    public ?string $icon = '📁';

    public ?int $client_id = null;

    public bool $is_visible_to_client = false;

    public string $priority = 'none';

    public ?string $due_on = null;

    public bool $is_paused = false;

    /**
     * Team of a new project (admins and members of the workspace).
     *
     * @var list<int>
     */
    public array $team_ids = [];

    public function setWorkspace(Workspace $workspace): void
    {
        $this->workspace = $workspace;
    }

    public function setProject(Project $project): void
    {
        $this->project = $project;
        $this->workspace = $project->workspace;
        $this->name = $project->name;
        $this->description = $project->description;
        $this->icon = $project->icon;
        $this->client_id = $project->client_id;
        $this->is_visible_to_client = $project->is_visible_to_client;
        $this->priority = $project->priority->value;
        $this->due_on = $project->due_on?->format('Y-m-d');
        $this->is_paused = $project->isPaused();
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'icon' => ['nullable', 'string', 'max:32'],
            'client_id' => ['nullable', 'integer', Rule::exists(Client::class, 'id')->where('workspace_id', $this->workspace?->id)->whereNull('deleted_at')],
            'is_visible_to_client' => ['boolean'],
            'priority' => ['required', Rule::enum(ProjectPriorityLevel::class)],
            'due_on' => ['nullable', 'date'],
            'is_paused' => ['boolean'],
            'team_ids' => ['array'],
            'team_ids.*' => ['integer', Rule::exists('workspace_user', 'user_id')->where('workspace_id', $this->workspace?->id)->whereIn('role', ['admin', 'member'])],
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
            'icon' => 'icono',
            'client_id' => 'cliente',
            'is_visible_to_client' => 'visible para el cliente',
            'priority' => 'prioridad',
            'due_on' => 'fecha de término',
            'is_paused' => 'en pausa',
            'team_ids' => 'equipo',
            'team_ids.*' => 'integrante',
        ];
    }

    public function store(User $creator): Project
    {
        $validated = $this->validate();

        $project = app(CreateProject::class)->handle($this->workspace, $creator, [
            'name' => $validated['name'],
            'description' => $validated['description'],
            'icon' => $validated['icon'],
            'client_id' => $validated['client_id'],
            'is_visible_to_client' => $validated['is_visible_to_client'],
            'priority' => $validated['priority'],
            'due_on' => $validated['due_on'] ?: null,
        ], array_map('intval', $validated['team_ids']));

        app(SetProjectPause::class)->handle($project, $creator, (bool) $validated['is_paused']);

        $this->reset('project', 'name', 'description', 'icon', 'client_id', 'is_visible_to_client', 'priority', 'due_on', 'is_paused', 'team_ids');

        return $project;
    }

    public function update(User $actor): void
    {
        $validated = $this->validate();

        $this->project->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'icon' => $validated['icon'],
            'client_id' => $validated['client_id'],
            'is_visible_to_client' => $validated['is_visible_to_client'],
            'priority' => $validated['priority'],
            'due_on' => $validated['due_on'] ?: null,
        ]);

        app(SetProjectPause::class)->handle($this->project, $actor, (bool) $validated['is_paused']);
    }
}
