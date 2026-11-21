<?php

namespace App\Livewire\Forms;

use App\Models\User;
use App\Models\Workspace;
use Livewire\Form;

/**
 * Name of a workspace, created only by a super admin (F-02, RN-25).
 */
class WorkspaceForm extends Form
{
    public ?Workspace $workspace = null;

    public string $name = '';

    public function setWorkspace(Workspace $workspace): void
    {
        $this->workspace = $workspace;
        $this->name = $workspace->name;
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'name' => 'nombre',
        ];
    }

    /**
     * Create a workspace in the account the user owns.
     */
    public function store(User $owner): Workspace
    {
        $validated = $this->validate();

        $workspace = Workspace::query()->create([
            'account_id' => $owner->ownedAccount->id,
            'name' => $validated['name'],
            'created_by_id' => $owner->id,
        ]);

        $this->reset();

        return $workspace;
    }

    public function update(): void
    {
        $this->workspace->update($this->validate());

        $this->reset();
    }
}
