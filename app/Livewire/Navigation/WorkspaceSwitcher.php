<?php

namespace App\Livewire\Navigation;

use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Workspace picker at the top of the sidebar (F-02: one workspace at a time).
 *
 * Lists the workspaces the user owns or was invited to (RN-03) and refreshes
 * on the `workspaces-changed` event.
 */
class WorkspaceSwitcher extends Component
{
    #[Computed]
    public function current(): ?Workspace
    {
        return app(CurrentWorkspace::class)->get();
    }

    /**
     * @return Collection<int, Workspace>
     */
    #[Computed]
    public function workspaces(): Collection
    {
        return Workspace::query()->visibleTo(auth()->user())->orderBy('name')->get(['id', 'uuid', 'name', 'account_id']);
    }

    public function switchTo(string $uuid): void
    {
        $workspace = Workspace::query()->visibleTo(auth()->user())->where('uuid', $uuid)->firstOrFail();

        app(CurrentWorkspace::class)->switchTo($workspace);

        $this->redirectRoute('dashboard', navigate: true);
    }

    #[On('workspaces-changed')]
    public function refreshWorkspaces(): void
    {
        unset($this->current, $this->workspaces);
    }

    public function render(): View
    {
        return view('livewire.navigation.workspace-switcher');
    }
}
