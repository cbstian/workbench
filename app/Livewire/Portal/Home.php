<?php

namespace App\Livewire\Portal;

use App\Livewire\Concerns\WithCurrentWorkspace;
use App\Models\Client;
use App\Models\Page;
use App\Models\Project;
use App\Models\Task;
use App\Support\ClientPortal;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Home of the client portal (P-04, P-06, P-14): the projects of the user's
 * clients marked visible, their progress, the upcoming due dates and the
 * workspace documentation shared with clients.
 */
#[Title('Inicio')]
class Home extends Component
{
    use WithCurrentWorkspace;

    /**
     * Days ahead covered by «Próximas entregas».
     */
    public const int UPCOMING_DAYS = 14;

    public const int UPCOMING_LIMIT = 8;

    public function mount(): void
    {
        $this->authorize('view-portal');
    }

    /**
     * Time-of-day greeting for the signed-in user, in Spanish.
     */
    #[Computed]
    public function greeting(): string
    {
        $hour = now()->hour;

        $salutation = match (true) {
            $hour < 6 => 'Buenas noches',
            $hour < 13 => 'Buenos días',
            $hour < 20 => 'Buenas tardes',
            default => 'Buenas noches',
        };

        return $salutation.', '.str(auth()->user()->name)->before(' ');
    }

    /**
     * Clients of the user in the workspace, each with its visible projects.
     *
     * @return Collection<int, Client>
     */
    #[Computed]
    public function clients(): Collection
    {
        $projects = $this->projects->groupBy('client_id');

        return auth()->user()->clients()
            ->where('clients.workspace_id', $this->workspace->id)
            ->orderBy('name')
            ->get()
            ->each(fn (Client $client) => $client->setRelation('projects', $projects->get($client->id, new Collection)));
    }

    /**
     * Visible projects with their task counts for the progress bar.
     *
     * @return Collection<int, Project>
     */
    #[Computed]
    public function projects(): Collection
    {
        return Project::query()
            ->visibleToClient(auth()->user(), $this->workspace)
            ->withCount([
                'rootTasks as tasks_total',
                'rootTasks as tasks_closed' => fn (Builder $query) => $query->whereNotNull('completed_at'),
                'rootTasks as tasks_overdue' => fn (Builder $query) => $query->openPastDue(),
            ])
            ->orderByRaw('paused_at is not null')
            ->orderBy('name')
            ->get();
    }

    /**
     * Open tasks due soon in the visible projects, earliest first.
     *
     * @return Collection<int, Task>
     */
    #[Computed]
    public function upcoming(): Collection
    {
        return Task::query()
            ->whereIn('project_id', $this->projects->pluck('id'))
            ->whereNull('completed_at')
            ->whereBetween('ends_on', [today()->toDateString(), today()->addDays(self::UPCOMING_DAYS)->toDateString()])
            ->with(['project:id,uuid,name,icon', 'status:id,name,color'])
            ->orderBy('ends_on')
            ->orderBy('name')
            ->limit(self::UPCOMING_LIMIT)
            ->get();
    }

    /**
     * Workspace wiki pages shared with clients, in tree order.
     *
     * @return Collection<int, Page>
     */
    #[Computed]
    public function documentation(): Collection
    {
        return app(ClientPortal::class)->visiblePages($this->workspace, null);
    }

    public function render(): View
    {
        return view('livewire.portal.home');
    }
}
