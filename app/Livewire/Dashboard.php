<?php

namespace App\Livewire;

use App\Actions\Tasks\MoveTaskToCategory;
use App\Enums\BoardEntryStatus;
use App\Enums\StatusCategory;
use App\Enums\WorkspaceRole;
use App\Models\BoardEntry;
use App\Models\Project;
use App\Models\ProjectPriority;
use App\Models\Task;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

/**
 * Panel home: a summary of the open work of the projects the user may open
 * (overdue, due today, the next seven days and what needs attention), for the
 * whole team or only the user's own tasks. A client user sees nothing yet (E10).
 *
 * Tasks of paused projects are left out, as on the board. Subtasks count like
 * any other task, since they carry their own dates and assignees.
 *
 * Logic lives here; markup lives in resources/views/livewire/dashboard.blade.php.
 */
#[Title('Inicio')]
class Dashboard extends Component
{
    use Interactions;

    /**
     * Tasks shown per list before linking to the tasks page.
     */
    public const int LIST_LIMIT = 10;

    /**
     * Days without changes after which a started task counts as stalled.
     */
    public const int STALLED_AFTER_DAYS = 14;

    /**
     * Days ahead covered by the agenda and the project deadline warning.
     */
    public const int UPCOMING_DAYS = 7;

    #[Url(as: 'mias', except: false)]
    public bool $onlyMine = false;

    /**
     * Client users have their own home: the portal of their projects.
     */
    public function mount(): void
    {
        if (auth()->user()->can('view-portal')) {
            $this->redirectRoute('portal.index', navigate: true);
        }
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

        $firstName = str(auth()->user()->name)->before(' ')->toString();

        return "{$salutation}, {$firstName}";
    }

    #[Computed]
    public function workspace(): ?Workspace
    {
        return app(CurrentWorkspace::class)->get();
    }

    /**
     * Whether the user is a client user of the workspace, who sees nothing for now (RN-04).
     */
    #[Computed]
    public function isClientUser(): bool
    {
        return $this->workspace !== null && auth()->user()->roleIn($this->workspace) === WorkspaceRole::Client;
    }

    /**
     * Names of the clients a client user belongs to in the current workspace.
     *
     * @return list<string>
     */
    #[Computed]
    public function clientNames(): array
    {
        if (! $this->isClientUser) {
            return [];
        }

        return auth()->user()->clients()
            ->where('clients.workspace_id', $this->workspace->id)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    /**
     * Projects the user may open that are not paused.
     *
     * @return Collection<int, Project>
     */
    #[Computed]
    public function projects(): Collection
    {
        if ($this->workspace === null || $this->isClientUser) {
            return new Collection;
        }

        return Project::query()
            ->visibleTo(auth()->user(), $this->workspace)
            ->whereNull('paused_at')
            ->get(['id', 'uuid', 'workspace_id', 'name', 'icon', 'due_on', 'paused_at']);
    }

    /**
     * Ids of the two most urgent priorities of each project, the initial one
     * excluded. Projects rename priorities, so the position decides (RN-16).
     *
     * @return list<int>
     */
    #[Computed]
    public function highPriorityIds(): array
    {
        return ProjectPriority::query()
            ->whereIn('project_id', $this->projects->modelKeys())
            ->where('is_initial', false)
            ->get(['id', 'project_id', 'position'])
            ->groupBy('project_id')
            ->flatMap(fn (SupportCollection $priorities) => $priorities->sortByDesc('position')->take(2)->pluck('id'))
            ->values()
            ->all();
    }

    /**
     * Overdue open tasks, most urgent first.
     *
     * @return SupportCollection<int, Task>
     */
    #[Computed]
    public function overdueTasks(): SupportCollection
    {
        return $this->sorted($this->openTasks()->where('ends_on', '<', today())->get());
    }

    /**
     * Open tasks due today or starting today.
     *
     * @return SupportCollection<int, Task>
     */
    #[Computed]
    public function todayTasks(): SupportCollection
    {
        return $this->sorted($this->openTasks()
            ->where(fn (Builder $query) => $query->whereDate('ends_on', today())->orWhereDate('starts_on', today()))
            ->get());
    }

    /**
     * Open tasks due in the next days (today's are in their own list), by day.
     *
     * @return SupportCollection<string, SupportCollection<int, Task>>
     */
    #[Computed]
    public function upcomingTasks(): SupportCollection
    {
        $tasks = $this->openTasks()
            ->whereBetween('ends_on', [today()->addDay(), today()->addDays(self::UPCOMING_DAYS)])
            ->where(fn (Builder $query) => $query->whereNull('starts_on')->orWhereDate('starts_on', '!=', today()))
            ->get();

        return $this->sorted($tasks)
            ->sortBy(fn (Task $task): string => $task->ends_on->toDateString())
            ->groupBy(fn (Task $task): string => $task->ends_on->toDateString());
    }

    #[Computed]
    public function upcomingCount(): int
    {
        return $this->upcomingTasks->sum(fn (SupportCollection $tasks): int => $tasks->count());
    }

    #[Computed]
    public function undatedCount(): int
    {
        return $this->openTasks()->whereNull('ends_on')->count();
    }

    /**
     * Figures shown as tiles, each linking to the tasks page.
     *
     * @return list<array{label: string, value: int, hint: string, href: string, tone: string|null}>
     */
    #[Computed]
    public function stats(): array
    {
        $today = today()->toDateString();

        return [
            [
                'label' => 'Atrasadas',
                'value' => $this->overdueTasks->count(),
                'hint' => $this->overdueHighCount() > 0 ? "{$this->overdueHighCount()} de alta prioridad" : 'Vencieron sin cerrarse',
                'href' => $this->tasksLink(['vista' => 'list', 'vencidas' => 1]),
                'tone' => 'danger',
            ],
            [
                'label' => 'Para hoy',
                'value' => $this->todayTasks->count(),
                'hint' => 'Vencen o empiezan hoy',
                'href' => $this->tasksLink(['vista' => 'list', 'desde' => $today, 'hasta' => $today]),
                'tone' => null,
            ],
            [
                'label' => 'Próximos '.self::UPCOMING_DAYS.' días',
                'value' => $this->upcomingCount,
                'hint' => 'Hasta el '.today()->addDays(self::UPCOMING_DAYS)->translatedFormat('l j'),
                'href' => $this->tasksLink(['vista' => 'calendar']),
                'tone' => null,
            ],
            [
                'label' => 'Sin fecha de término',
                'value' => $this->undatedCount,
                'hint' => 'Abiertas sin plazo',
                'href' => $this->tasksLink(['vista' => 'list']),
                'tone' => null,
            ],
        ];
    }

    /**
     * Warnings about work that needs a decision, only those with a count.
     *
     * @return list<array{key: string, title: string, description: string, count: int, examples: list<array{label: string, meta: string, href: string}>, href: string}>
     */
    #[Computed]
    public function attention(): array
    {
        $items = [];

        $highUndated = $this->openTasks()->whereNull('ends_on')->whereIn('priority_id', $this->highPriorityIds);
        $items[] = $this->taskWarning(
            'high-undated',
            'Alta prioridad sin fecha',
            'Urgentes que no tienen plazo de término.',
            $highUndated,
            $this->tasksLink(['vista' => 'list']),
        );

        if (! $this->onlyMine) {
            $unassigned = $this->openTasks()
                ->whereDoesntHave('assignees')
                ->whereNotNull('ends_on')
                ->where('ends_on', '<=', today()->addDays(self::UPCOMING_DAYS));
            $items[] = $this->taskWarning(
                'unassigned',
                'Sin responsable',
                'Vencen pronto o ya vencieron y nadie las tiene asignadas.',
                $unassigned,
                $this->tasksLink(['vista' => 'list', 'hasta' => today()->addDays(self::UPCOMING_DAYS)->toDateString()]),
            );
        }

        $stalled = $this->openTasks()
            ->whereHas('status', fn (Builder $query) => $query->where('category', StatusCategory::Started))
            ->where('tasks.updated_at', '<', now()->subDays(self::STALLED_AFTER_DAYS));
        $items[] = $this->taskWarning(
            'stalled',
            'Detenidas',
            'En curso sin cambios hace más de '.self::STALLED_AFTER_DAYS.' días.',
            $stalled,
            $this->tasksLink(['vista' => 'kanban']),
        );

        $items[] = $this->projectDeadlineWarning();

        return array_values(array_filter($items, fn (array $item): bool => $item['count'] > 0));
    }

    /**
     * Planned board notes of today, for the projects shown.
     *
     * @return Collection<int, BoardEntry>
     */
    #[Computed]
    public function todayBoardEntries(): Collection
    {
        return BoardEntry::query()
            ->whereIn('project_id', $this->projects->modelKeys())
            ->whereDate('occurs_on', today())
            ->where('status', BoardEntryStatus::Planned)
            ->with('project:id,uuid,name,icon')
            ->orderBy('position')
            ->get();
    }

    /**
     * Header line: today's date plus what is most pressing.
     */
    #[Computed]
    public function summary(): string
    {
        $date = str(today()->translatedFormat('l j \\d\\e F'))->ucfirst()->toString();
        $overdue = $this->overdueTasks->count();

        if ($overdue === 0) {
            return $this->todayTasks->isEmpty()
                ? "{$date}. No hay nada atrasado ni pendiente para hoy."
                : "{$date}. Nada atrasado; ".trans_choice('{1} queda :count tarea para hoy.|[2,*] quedan :count tareas para hoy.', $this->todayTasks->count());
        }

        $line = trans_choice('{1} Hay :count tarea atrasada|[2,*] Hay :count tareas atrasadas', $overdue);

        if ($this->overdueHighCount() > 0) {
            $line .= ', '.trans_choice('{1} :count de alta prioridad|[2,*] :count de alta prioridad', $this->overdueHighCount());
        }

        return "{$date}. {$line}.";
    }

    /**
     * Whether a task has one of the most urgent priorities of its project.
     */
    public function isHighPriority(Task $task): bool
    {
        return in_array($task->priority_id, $this->highPriorityIds, true);
    }

    /**
     * Closes a task: it moves to the first "completed" status of its project.
     */
    public function complete(int $taskId): void
    {
        $task = Task::query()
            ->whereIn('project_id', $this->projects->modelKeys())
            ->whereNull('completed_at')
            ->with('status')
            ->findOrFail($taskId);

        if (auth()->user()->cannot('update', $task)) {
            $this->toast()->warning('No puedes cerrar esta tarea', 'Solo la cierran los admins y quienes la tienen asignada.')->send();

            return;
        }

        if (app(MoveTaskToCategory::class)->handle($task, auth()->user(), StatusCategory::Completed) === null) {
            $this->toast()->warning('No se pudo cerrar', 'El proyecto no tiene un estado «'.StatusCategory::Completed->label().'».')->send();

            return;
        }

        $this->toast()->success('Tarea completada', $task->name)->send();
    }

    public function render(): View
    {
        return view('livewire.dashboard');
    }

    /**
     * Open tasks of the shown projects, with what a row needs.
     *
     * @return Builder<Task>
     */
    private function openTasks(): Builder
    {
        return Task::query()
            ->whereIn('project_id', $this->projects->modelKeys())
            ->whereNull('completed_at')
            ->when($this->onlyMine, fn (Builder $query) => $query->whereHas('assignees', fn (Builder $assignees) => $assignees->whereKey(auth()->id())))
            ->with(['project:id,uuid,workspace_id,name,icon', 'project.workspace', 'priority', 'assignees:id,name', 'parent:id,uuid,name'])
            ->withCount(['subtasks', 'subtasks as closed_subtasks_count' => fn (Builder $query) => $query->whereNotNull('completed_at')]);
    }

    /**
     * Most urgent priority first, then the earliest due date, then by name.
     *
     * @param  Collection<int, Task>  $tasks
     * @return SupportCollection<int, Task>
     */
    private function sorted(Collection $tasks): SupportCollection
    {
        return $tasks->toBase()->sortBy([
            fn (Task $a, Task $b): int => ($b->priority?->position ?? 0) <=> ($a->priority?->position ?? 0),
            fn (Task $a, Task $b): int => ($a->ends_on?->timestamp ?? PHP_INT_MAX) <=> ($b->ends_on?->timestamp ?? PHP_INT_MAX),
            fn (Task $a, Task $b): int => strnatcasecmp($a->name, $b->name),
        ])->values();
    }

    private function overdueHighCount(): int
    {
        return $this->overdueTasks->filter(fn (Task $task): bool => $this->isHighPriority($task))->count();
    }

    /**
     * Link to the tasks page, scoped to the user when "Solo mías" is on.
     *
     * @param  array<string, mixed>  $parameters
     */
    private function tasksLink(array $parameters): string
    {
        if ($this->onlyMine) {
            $parameters['asignados'] = [auth()->id()];
        }

        return route('tasks.index', $parameters);
    }

    /**
     * @param  Builder<Task>  $query
     * @return array{key: string, title: string, description: string, count: int, examples: list<array{label: string, meta: string, href: string}>, href: string}
     */
    private function taskWarning(string $key, string $title, string $description, Builder $query, string $href): array
    {
        $count = (clone $query)->count();

        $examples = $count === 0 ? [] : $this->sorted((clone $query)->limit(30)->get())
            ->take(3)
            ->map(fn (Task $task): array => [
                'label' => $task->name,
                'meta' => trim($task->project->icon.' '.$task->project->name),
                'href' => route('tasks.show', $task),
            ])
            ->all();

        return compact('key', 'title', 'description', 'count', 'examples', 'href');
    }

    /**
     * Projects whose deadline passed or comes within the next days.
     *
     * @return array{key: string, title: string, description: string, count: int, examples: list<array{label: string, meta: string, href: string}>, href: string}
     */
    private function projectDeadlineWarning(): array
    {
        $projects = $this->projects
            ->filter(fn (Project $project): bool => $project->due_on !== null && $project->due_on->lte(today()->addDays(self::UPCOMING_DAYS)))
            ->sortBy('due_on')
            ->values();

        return [
            'key' => 'project-deadlines',
            'title' => 'Proyectos por vencer',
            'description' => 'Vencidos o con término en los próximos '.self::UPCOMING_DAYS.' días.',
            'count' => $projects->count(),
            'examples' => $projects->take(3)->map(fn (Project $project): array => [
                'label' => trim($project->icon.' '.$project->name),
                'meta' => $project->due_on->isPast() && ! $project->due_on->isToday()
                    ? 'Venció el '.$project->due_on->translatedFormat('j M')
                    : 'Vence '.($project->due_on->isToday() ? 'hoy' : 'el '.$project->due_on->translatedFormat('l j')),
                'href' => route('projects.show', $project),
            ])->all(),
            'href' => route('board.index'),
        ];
    }
}
