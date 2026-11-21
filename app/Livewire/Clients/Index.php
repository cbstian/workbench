<?php

namespace App\Livewire\Clients;

use App\Livewire\Concerns\WithCurrentWorkspace;
use App\Livewire\Forms\ClientForm;
use App\Models\Client;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use TallStackUi\Traits\Interactions;

/**
 * Clients of the current workspace, managed only by the super admin (E2, F-03).
 */
#[Title('Clientes')]
class Index extends Component
{
    use Interactions, WithCurrentWorkspace, WithFileUploads;

    public ClientForm $form;

    public bool $showForm = false;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', [Client::class, $this->workspace]);
    }

    /**
     * @return Collection<int, Client>
     */
    #[Computed]
    public function clients(): Collection
    {
        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $this->search).'%';

        return $this->workspace->clients()
            ->when($this->search !== '', fn ($query) => $query->whereLike('name', $like))
            ->withCount(['projects', 'users'])
            ->orderBy('name')
            ->get();
    }

    public function create(): void
    {
        $this->authorize('create', [Client::class, $this->workspace]);

        $this->form->reset();
        $this->form->resetValidation();
        $this->form->setWorkspace($this->workspace);
        $this->showForm = true;
    }

    public function edit(string $uuid): void
    {
        $client = $this->findClient($uuid);
        $this->authorize('update', $client);

        $this->form->resetValidation();
        $this->form->setClient($client);
        $this->showForm = true;
    }

    public function save(): void
    {
        if ($this->form->client !== null) {
            $this->authorize('update', $this->form->client);
            $this->form->update();
            $this->toast()->success('Cliente actualizado')->send();
        } else {
            $this->authorize('create', [Client::class, $this->workspace]);
            $this->form->store(auth()->user());
            $this->toast()->success('Cliente creado', 'Ya puedes relacionarle proyectos y usuarios cliente.')->send();
        }

        $this->showForm = false;
        unset($this->clients);
    }

    /**
     * Discard the logo picked in the upload field (TallStackUI delete button).
     */
    public function deleteUpload(): void
    {
        $this->form->logo = null;
    }

    public function confirmDelete(string $uuid): void
    {
        $client = $this->findClient($uuid);

        $this->dialog()
            ->question("¿Enviar «{$client->name}» a la papelera?", 'Sus proyectos siguen activos, sin cliente visible, hasta que lo restaures.')
            ->confirm('Enviar a la papelera', 'delete', $uuid)
            ->cancel('Cancelar')
            ->send();
    }

    public function delete(string $uuid): void
    {
        $client = $this->findClient($uuid);
        $this->authorize('delete', $client);

        $client->delete();

        $this->toast()->success('Cliente enviado a la papelera', 'Puedes restaurarlo desde la papelera.')->send();
        unset($this->clients);
    }

    private function findClient(string $uuid): Client
    {
        return $this->workspace->clients()->where('uuid', $uuid)->firstOrFail();
    }

    public function render(): View
    {
        return view('livewire.clients.index');
    }
}
