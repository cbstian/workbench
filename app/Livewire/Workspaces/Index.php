<?php

namespace App\Livewire\Workspaces;

use App\Livewire\Forms\WorkspaceForm;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

/**
 * Workspaces of the super admin's account: create, rename, open, send to the
 * trash and restore (E1, F-02, RN-19).
 */
#[Title('Espacios de trabajo')]
class Index extends Component
{
    use Interactions;

    public WorkspaceForm $form;

    public bool $showForm = false;

    public function mount(): void
    {
        $this->authorize('viewAny', Workspace::class);
    }

    /**
     * Active workspaces of the account, with their counts.
     *
     * @return Collection<int, Workspace>
     */
    #[Computed]
    public function workspaces(): Collection
    {
        return $this->accountWorkspaces()
            ->withCount(['projects', 'clients', 'members'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Workspaces of the account in the trash.
     *
     * @return Collection<int, Workspace>
     */
    #[Computed]
    public function trashedWorkspaces(): Collection
    {
        return $this->accountWorkspaces()->onlyTrashed()->with('deleter')->latest('deleted_at')->get();
    }

    #[Computed]
    public function currentWorkspaceId(): ?int
    {
        return app(CurrentWorkspace::class)->get()?->id;
    }

    public function create(): void
    {
        $this->form->reset();
        $this->form->resetValidation();
        $this->showForm = true;
    }

    public function edit(string $uuid): void
    {
        $workspace = $this->findWorkspace($uuid);
        $this->authorize('update', $workspace);

        $this->form->resetValidation();
        $this->form->setWorkspace($workspace);
        $this->showForm = true;
    }

    public function save(): void
    {
        if ($this->form->workspace !== null) {
            $this->authorize('update', $this->form->workspace);
            $this->form->update();
            $this->toast()->success('Espacio actualizado')->send();
        } else {
            $this->authorize('create', Workspace::class);
            $workspace = $this->form->store(auth()->user());

            if ($this->currentWorkspaceId === null) {
                app(CurrentWorkspace::class)->switchTo($workspace);
            }

            $this->toast()->success('Espacio creado', 'Ábrelo para crear sus clientes, proyectos y usuarios.')->send();
        }

        $this->showForm = false;
        $this->dispatch('workspaces-changed');
        unset($this->workspaces, $this->currentWorkspaceId);
    }

    /**
     * Work in the workspace and go to its projects.
     */
    public function open(string $uuid): void
    {
        $workspace = $this->findWorkspace($uuid);
        $this->authorize('view', $workspace);

        app(CurrentWorkspace::class)->switchTo($workspace);

        $this->redirectRoute('projects.index', navigate: true);
    }

    public function confirmDelete(string $uuid): void
    {
        $workspace = $this->findWorkspace($uuid);

        $this->dialog()
            ->question("¿Enviar «{$workspace->name}» a la papelera?", 'Sus clientes, proyectos y wiki quedan ocultos hasta que lo restaures.')
            ->confirm('Enviar a la papelera', 'delete', $uuid)
            ->cancel('Cancelar')
            ->send();
    }

    public function delete(string $uuid): void
    {
        $workspace = $this->findWorkspace($uuid);
        $this->authorize('delete', $workspace);

        $workspace->delete();

        if ($this->currentWorkspaceId === $workspace->id) {
            app(CurrentWorkspace::class)->forget();
        }

        $this->toast()->success('Espacio enviado a la papelera')->send();
        $this->dispatch('workspaces-changed');
        unset($this->workspaces, $this->trashedWorkspaces, $this->currentWorkspaceId);
    }

    public function restore(string $uuid): void
    {
        $workspace = $this->accountWorkspaces()->onlyTrashed()->where('uuid', $uuid)->firstOrFail();
        $this->authorize('restore', $workspace);

        $workspace->restore();

        $this->toast()->success('Espacio restaurado')->send();
        $this->dispatch('workspaces-changed');
        unset($this->workspaces, $this->trashedWorkspaces);
    }

    /**
     * @return Builder<Workspace>
     */
    private function accountWorkspaces(): Builder
    {
        return Workspace::query()->where('account_id', auth()->user()->ownedAccount?->id);
    }

    private function findWorkspace(string $uuid): Workspace
    {
        return $this->accountWorkspaces()->where('uuid', $uuid)->firstOrFail();
    }

    public function render(): View
    {
        return view('livewire.workspaces.index');
    }
}
