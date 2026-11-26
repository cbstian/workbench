<?php

namespace App\Livewire\Navigation;

use App\Enums\WorkspaceRole;
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
 * on the `workspaces-changed` event. A client user stays in their workspace:
 * the picker is hidden and switching is refused.
 */
class WorkspaceSwitcher extends Component
{
    #[Computed]
    public function current(): ?Workspace
    {
        return app(CurrentWorkspace::class)->get();
    }

    /**
     * Whether the user may see the picker and change workspace: anyone but a
     * client user of the current workspace.
     */
    #[Computed]
    public function canSwitch(): bool
    {
        return $this->current === null
            || auth()->user()->roleIn($this->current) !== WorkspaceRole::Client;
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
        abort_unless($this->canSwitch, 403);

        $workspace = Workspace::query()->visibleTo(auth()->user())->where('uuid', $uuid)->firstOrFail();

        app(CurrentWorkspace::class)->switchTo($workspace);

        $this->redirectRoute('dashboard', navigate: true);
    }

    #[On('workspaces-changed')]
    public function refreshWorkspaces(): void
    {
        unset($this->current, $this->canSwitch, $this->workspaces);
    }

    public function render(): View
    {
        return view('livewire.navigation.workspace-switcher');
    }
}
