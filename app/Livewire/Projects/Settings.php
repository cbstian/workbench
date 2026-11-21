<?php

namespace App\Livewire\Projects;

use App\Livewire\Forms\ProjectForm;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

/**
 * Project settings (F-05): general data, team, statuses, priorities and labels.
 *
 * Statuses, priorities and labels live in their own components
 * (WorkflowValues, TaskLabels); this page holds the general data, the team
 * (only the super admin adds existing users, RN-26) and sending to the trash.
 */
class Settings extends Component
{
    use Interactions;

    public Project $project;

    public ProjectForm $form;

    /**
     * @var list<int>
     */
    public array $newMemberIds = [];

    public function mount(): void
    {
        $this->authorize('configure', $this->project);

        $this->form->setProject($this->project);
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    #[Computed]
    public function clientOptions(): array
    {
        return $this->project->workspace->clients()->orderBy('name')->get(['id', 'name'])
            ->map(fn ($client): array => ['label' => $client->name, 'value' => $client->id])
            ->all();
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function team(): Collection
    {
        return $this->project->members()
            ->with(['workspaces' => fn ($query) => $query->whereKey($this->project->workspace_id)])
            ->orderBy('name')
            ->get();
    }

    /**
     * Admins and members of the workspace not yet on the team.
     *
     * @return list<array{label: string, value: int, description: string}>
     */
    #[Computed]
    public function candidateOptions(): array
    {
        return $this->project->workspace->members()
            ->wherePivotIn('role', ['admin', 'member'])
            ->whereNotIn('users.id', $this->team->modelKeys())
            ->orderBy('name')
            ->get(['users.id', 'users.name', 'users.email'])
            ->map(fn (User $user): array => ['label' => $user->name, 'value' => $user->id, 'description' => $user->pivot->role->label().' · '.$user->email])
            ->all();
    }

    public function saveGeneral(): void
    {
        $this->authorize('update', $this->project);

        $this->form->update();

        $this->dispatch('projects-changed');
        $this->toast()->success('Proyecto actualizado')->send();
    }

    public function addMembers(): void
    {
        $this->authorize('manageTeam', $this->project);

        $validated = $this->validate([
            'newMemberIds' => ['required', 'array', 'min:1'],
            'newMemberIds.*' => ['integer', 'in:'.collect($this->candidateOptions)->pluck('value')->implode(',')],
        ], attributes: ['newMemberIds' => 'integrantes', 'newMemberIds.*' => 'integrante']);

        $this->project->members()->attach(array_fill_keys(array_map('intval', $validated['newMemberIds']), ['created_by_id' => auth()->id()]));

        $this->newMemberIds = [];
        unset($this->team, $this->candidateOptions);
        $this->toast()->success('Equipo actualizado')->send();
    }

    public function confirmRemoveMember(int $userId): void
    {
        $member = $this->team->firstWhere('id', $userId);
        abort_if($member === null, 404);

        $this->dialog()
            ->question("¿Quitar a {$member->name} del equipo?", 'Deja de ver el proyecto y se quita de las tareas que tenía asignadas.')
            ->confirm('Quitar del equipo', 'removeMember', $userId)
            ->cancel('Cancelar')
            ->send();
    }

    public function removeMember(int $userId): void
    {
        $this->authorize('manageTeam', $this->project);

        DB::table('project_user')->where('project_id', $this->project->id)->where('user_id', $userId)->delete();

        unset($this->team, $this->candidateOptions);
        $this->toast()->success('Integrante quitado del equipo')->send();
    }

    public function confirmDelete(): void
    {
        $this->dialog()
            ->question("¿Enviar «{$this->project->name}» a la papelera?", 'Sus tareas y su wiki quedan ocultas hasta que lo restaures.')
            ->confirm('Enviar a la papelera', 'delete')
            ->cancel('Cancelar')
            ->send();
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->project);

        $this->project->delete();

        $this->dispatch('projects-changed');
        $this->toast()->success('Proyecto enviado a la papelera')->flash()->send();
        $this->redirectRoute('projects.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.projects.settings')->title('Configurar · '.$this->project->name);
    }
}
