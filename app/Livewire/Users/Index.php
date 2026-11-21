<?php

namespace App\Livewire\Users;

use App\Enums\WorkspaceRole;
use App\Livewire\Concerns\WithCurrentWorkspace;
use App\Livewire\Forms\WorkspaceUserForm;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

/**
 * Users of the current workspace and their roles (E3, F-04).
 *
 * The super admin creates admins, members and client users, adds users of
 * the account, changes roles and access, removes users from the workspace,
 * and deactivates, deletes or restores the users of the account (RN-14).
 */
#[Title('Usuarios')]
class Index extends Component
{
    use Interactions, WithCurrentWorkspace;

    public WorkspaceUserForm $form;

    public bool $showForm = false;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $role = '';

    public function mount(): void
    {
        $this->authorize('manageMembers', $this->workspace);
    }

    /**
     * Members of the workspace with their role, clients and project count.
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function members(): Collection
    {
        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $this->search).'%';
        $workspaceId = $this->workspace->id;

        return $this->workspace->members()
            ->when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query->whereLike('users.name', $like)->orWhereLike('users.email', $like)))
            ->when(WorkspaceRole::tryFrom($this->role), fn ($query, WorkspaceRole $role) => $query->wherePivot('role', $role->value))
            ->with(['clients' => fn ($query) => $query->where('clients.workspace_id', $workspaceId)->orderBy('name')])
            ->withCount(['projects' => fn ($query) => $query->where('projects.workspace_id', $workspaceId)])
            ->orderBy('users.name')
            ->get();
    }

    /**
     * Counts per role for the filter.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function roleCounts(): array
    {
        return $this->workspace->members()
            ->toBase()
            ->selectRaw('workspace_user.role, count(*) as total')
            ->groupBy('workspace_user.role')
            ->pluck('total', 'role')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }

    /**
     * Users of the account in the trash (logical delete, RN-14).
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function deletedUsers(): Collection
    {
        return User::onlyTrashed()
            ->where('account_id', $this->workspace->account_id)
            ->latest('deleted_at')
            ->get();
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    #[Computed]
    public function clientOptions(): array
    {
        return $this->workspace->clients()->orderBy('name')->get(['id', 'name'])
            ->map(fn ($client): array => ['label' => $client->name, 'value' => $client->id])
            ->all();
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    #[Computed]
    public function projectOptions(): array
    {
        return $this->workspace->projects()->orderBy('name')->get(['id', 'name', 'icon'])
            ->map(fn ($project): array => ['label' => trim(($project->icon ?? '').' '.$project->name), 'value' => $project->id])
            ->all();
    }

    /**
     * @return list<array{label: string, value: int, description: string}>
     */
    #[Computed]
    public function addableUserOptions(): array
    {
        if ($this->form->mode !== WorkspaceUserForm::MODE_EXISTING) {
            return [];
        }

        return $this->form->addableUsersQuery()->orderBy('name')->get(['id', 'name', 'email'])
            ->map(fn (User $user): array => ['label' => $user->name, 'value' => $user->id, 'description' => $user->email])
            ->all();
    }

    public function create(): void
    {
        $this->form->start(WorkspaceUserForm::MODE_CREATE, $this->workspace);
        $this->form->resetValidation();
        $this->showForm = true;
    }

    public function addExisting(): void
    {
        $this->form->start(WorkspaceUserForm::MODE_EXISTING, $this->workspace);
        $this->form->resetValidation();
        unset($this->addableUserOptions);
        $this->showForm = true;
    }

    public function edit(string $uuid): void
    {
        $user = $this->findMember($uuid);
        $this->authorize('update', $user);

        $this->form->setUser($user, $this->workspace);
        $this->form->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('manageMembers', $this->workspace);

        if ($this->form->mode === WorkspaceUserForm::MODE_EDIT) {
            $this->authorize('update', $this->form->user);
            $this->form->update(auth()->user());
        } elseif ($this->form->mode === WorkspaceUserForm::MODE_EXISTING) {
            $this->form->addExisting(auth()->user());
        } else {
            $this->form->store(auth()->user());
        }

        $this->toast()->success(match ($this->form->mode) {
            WorkspaceUserForm::MODE_CREATE => 'Usuario creado',
            WorkspaceUserForm::MODE_EXISTING => 'Usuario agregado al espacio',
            default => 'Usuario actualizado',
        })->send();

        $this->showForm = false;
        $this->refreshLists();
    }

    public function toggleActive(string $uuid): void
    {
        $user = $this->findMember($uuid);
        $this->authorize('deactivate', $user);

        $user->forceFill($user->deactivated_at === null
            ? ['deactivated_at' => now(), 'deactivated_by_id' => auth()->id()]
            : ['deactivated_at' => null, 'deactivated_by_id' => null])->save();

        $user->deactivated_at === null
            ? $this->toast()->success('Acceso reactivado', "{$user->name} puede volver a entrar.")->send()
            : $this->toast()->success('Acceso desactivado', "{$user->name} ya no puede entrar. Sus datos se conservan.")->send();

        $this->refreshLists();
    }

    public function confirmRemove(string $uuid): void
    {
        $user = $this->findMember($uuid);

        $this->dialog()
            ->question("¿Quitar a {$user->name} de {$this->workspace->name}?", 'Pierde el acceso a este espacio, a sus proyectos y a sus asignaciones. Su usuario sigue existiendo.')
            ->confirm('Quitar del espacio', 'remove', $uuid)
            ->cancel('Cancelar')
            ->send();
    }

    public function remove(string $uuid): void
    {
        $this->authorize('manageMembers', $this->workspace);
        $user = $this->findMember($uuid);

        $this->workspace->members()->detach($user->id);

        $this->toast()->success('Usuario quitado del espacio')->send();
        $this->refreshLists();
    }

    public function confirmDelete(string $uuid): void
    {
        $user = $this->findMember($uuid);

        $this->dialog()
            ->question("¿Eliminar a {$user->name}?", 'No podrá entrar a ningún espacio. El borrado es lógico: su información se conserva y puedes restaurarlo.')
            ->confirm('Eliminar usuario', 'delete', $uuid)
            ->cancel('Cancelar')
            ->send();
    }

    public function delete(string $uuid): void
    {
        $user = $this->findMember($uuid);
        $this->authorize('delete', $user);

        $user->delete();
        $user->forceFill(['deleted_by_id' => auth()->id()])->saveQuietly();

        $this->toast()->success('Usuario eliminado', 'Puedes restaurarlo desde «Usuarios eliminados».')->send();
        $this->refreshLists();
    }

    public function restore(string $uuid): void
    {
        $user = User::onlyTrashed()->where('uuid', $uuid)->firstOrFail();
        $this->authorize('restore', $user);

        $user->restore();
        $user->forceFill(['deleted_by_id' => null])->saveQuietly();

        $this->toast()->success('Usuario restaurado')->send();
        $this->refreshLists();
    }

    private function findMember(string $uuid): User
    {
        return $this->workspace->members()->where('users.uuid', $uuid)->firstOrFail();
    }

    private function refreshLists(): void
    {
        unset($this->members, $this->roleCounts, $this->deletedUsers);
    }

    public function render(): View
    {
        return view('livewire.users.index');
    }
}
