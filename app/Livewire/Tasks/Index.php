<?php

namespace App\Livewire\Tasks;

use App\Actions\Tasks\MoveTask;
use App\Enums\StatusCategory;
use App\Enums\TaskView;
use App\Livewire\Concerns\WithCurrentWorkspace;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\Task;
use App\Models\User;
use App\Support\TaskCalendar;
use App\Support\TaskFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

/**
 * Root tasks of every project the user may open, in the same three views as
 * a project (kanban, list, calendar). The kanban groups by status category,
 * since each project names its own statuses. View and filters live in the URL.
 *
 * Logic lives here; markup lives in resources/views/livewire/tasks/index.blade.php.
 */
#[Title('Tareas')]
class Index extends Component
{
    use Interactions, WithCurrentWorkspace;

    /**
     * Columns the list view may sort by.
     *
     * @var list<string>
     */
    public const array LIST_SORTS = ['project', 'name', 'status', 'priority', 'ends_on'];

    #[Url(as: 'vista', except: 'kanban')]
    public string $view = 'kanban';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /**
     * @var list<int>
     */
    #[Url(as: 'proyectos', except: [])]
    public array $projectIds = [];

    /**
     * @var list<int>
     */
    #[Url(as: 'clientes', except: [])]
    public array $clientIds = [];

    /**
     * @var list<int>
     */
    #[Url(as: 'asignados', except: [])]
    public array $assigneeIds = [];

    /**
     * Status categories (StatusCategory values).
     *
     * @var list<string>
     */
    #[Url(as: 'estados', except: [])]
    public array $categories = [];

    #[Url(as: 'desde', except: '')]
    public ?string $dateFrom = '';

    #[Url(as: 'hasta', except: '')]
    public ?string $dateTo = '';

    #[Url(as: 'vencidas', except: false)]
    public bool $onlyOverdue = false;

    #[Url(as: 'sin_pausados', except: false)]
    public bool $hidePaused = false;

    /**
     * Completed and cancelled tasks are hidden unless asked for.
     */
    #[Url(as: 'cerradas', except: false)]
    public bool $showClosed = false;

    /**
     * Month shown in the calendar (Y-m).
     */
    #[Url(as: 'mes', except: '')]
    public string $month = '';

    public string $listSort = 'project';

    public string $listDirection = 'asc';

    /**
     * Client users have no cross-project view, like the board (E10).
     */
    public function mount(): void
    {
        $this->authorize('view-projects');

        if (TaskView::tryFrom($this->view) === null) {
            $this->view = TaskView::Kanban->value;
        }
    }

    public function updatedView(): void
    {
        if (TaskView::tryFrom($this->view) === null) {
            $this->view = TaskView::Kanban->value;
        }
    }

    public function onlyMine(): void
    {
        $this->assigneeIds = [(int) auth()->id()];
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'projectIds', 'clientIds', 'assigneeIds', 'categories', 'dateFrom', 'dateTo', 'onlyOverdue', 'hidePaused', 'showClosed');
    }

    /**
     * Number of active filters, search and the closed toggle excluded.
     */
    #[Computed]
    public function activeFilters(): int
    {
        return count(array_filter([
            $this->projectIds,
            $this->clientIds,
            $this->assigneeIds,
            $this->categories,
            filled($this->dateFrom) || filled($this->dateTo),
            $this->onlyOverdue,
            $this->hidePaused,
        ]));
    }

    /**
     * Every project the user may open, in the shared board order.
     *
     * @return Collection<int, Project>
     */
    #[Computed]
    public function visibleProjects(): Collection
    {
        return Project::query()
            ->visibleTo(auth()->user(), $this->workspace)
            ->onBoard()
            ->get(['id', 'uuid', 'workspace_id', 'client_id', 'name', 'icon', 'due_on', 'paused_at', 'board_position']);
    }

    /**
     * Visible projects left after the project, client and pause filters.
     *
     * @return Collection<int, Project>
     */
    #[Computed]
    public function projects(): Collection
    {
        $projectIds = $this->ids($this->projectIds);
        $clientIds = $this->ids($this->clientIds);

        return $this->visibleProjects
            ->when($projectIds !== [], fn (Collection $projects) => $projects->whereIn('id', $projectIds))
            ->when($clientIds !== [], fn (Collection $projects) => $projects->whereIn('client_id', $clientIds))
            ->when($this->hidePaused, fn (Collection $projects) => $projects->reject(fn (Project $project): bool => $project->isPaused()))
            ->values();
    }

    /**
     * Status categories the view shows, in workflow order.
     *
     * @return list<StatusCategory>
     */
    #[Computed]
    public function shownCategories(): array
    {
        $chosen = array_values(array_filter(array_map(
            fn (mixed $value): ?StatusCategory => StatusCategory::tryFrom((string) $value),
            $this->categories,
        )));

        return array_values(array_filter(
            StatusCategory::cases(),
            fn (StatusCategory $category): bool => $chosen !== []
                ? in_array($category, $chosen, true)
                : ($this->showClosed || ! $category->isClosed()),
        ));
    }

    /**
     * Root tasks of the shown projects after the filters, in board order.
     *
     * @return SupportCollection<int, Task>
     */
    #[Computed]
    public function tasks(): SupportCollection
    {
        $projects = $this->projects;

        if ($projects->isEmpty() || $this->shownCategories === []) {
            return collect();
        }

        $order = $projects->pluck('id')->flip();
        $categories = array_map(fn (StatusCategory $category): string => $category->value, $this->shownCategories);

        $filters = new TaskFilters(
            assigneeIds: $this->ids($this->assigneeIds),
            from: $this->date($this->dateFrom),
            to: $this->date($this->dateTo),
            search: filled($this->search) ? mb_substr(trim($this->search), 0, 100) : null,
        );

        $tasks = $filters->apply(Task::query())
            ->whereNull('parent_id')
            ->whereIn('project_id', $projects->modelKeys())
            ->whereHas('status', fn (Builder $query) => $query->whereIn('category', $categories))
            ->when($this->onlyOverdue, fn (Builder $query) => $query->openPastDue())
            ->with(['status', 'priority', 'assignees:id,name', 'labels:id,name,color'])
            ->withCount(['subtasks', 'subtasks as closed_subtasks_count' => fn (Builder $query) => $query->whereNotNull('completed_at')])
            ->get();

        $byId = $projects->keyBy('id');

        return $tasks
            ->each(fn (Task $task) => $task->setRelation('project', $byId->get($task->project_id)))
            ->sortBy([
                fn (Task $a, Task $b): int => $order[$a->project_id] <=> $order[$b->project_id],
                fn (Task $a, Task $b): int => $a->position <=> $b->position,
                fn (Task $a, Task $b): int => $a->id <=> $b->id,
            ])
            ->values();
    }

    /**
     * Tasks of the list view in the chosen order.
     *
     * @return SupportCollection<int, Task>
     */
    #[Computed]
    public function listTasks(): SupportCollection
    {
        $sort = in_array($this->listSort, self::LIST_SORTS, true) ? $this->listSort : 'project';
        $categoryOrder = array_flip(array_map(fn (StatusCategory $category): string => $category->value, StatusCategory::cases()));

        $sorted = match ($sort) {
            'name' => $this->tasks->sortBy(fn (Task $task): string => mb_strtolower($task->name)),
            'status' => $this->tasks->sortBy(fn (Task $task): array => [$categoryOrder[$task->status->category->value], $task->status->position]),
            'priority' => $this->tasks->sortBy(fn (Task $task): int => $task->priority->position),
            'ends_on' => $this->tasks->sortBy(fn (Task $task): string => $task->ends_on?->format('Y-m-d') ?? '9999-12-31'),
            default => $this->tasks,
        };

        return ($this->listDirection === 'desc' ? $sorted->reverse() : $sorted)->values();
    }

    public function sortList(string $column): void
    {
        if (! in_array($column, self::LIST_SORTS, true)) {
            return;
        }

        $this->listDirection = $this->listSort === $column && $this->listDirection === 'asc' ? 'desc' : 'asc';
        $this->listSort = $column;
    }

    /**
     * @return list<list<array{date: Carbon, inMonth: bool, isToday: bool, tasks: SupportCollection<int, Task>}>>
     */
    #[Computed]
    public function calendarWeeks(): array
    {
        return TaskCalendar::weeks($this->calendarMonth(), $this->tasks);
    }

    #[Computed]
    public function undatedCount(): int
    {
        return TaskCalendar::undatedCount($this->tasks);
    }

    /**
     * Shown projects that end on each day, keyed by Y-m-d.
     *
     * @return SupportCollection<string, Collection<int, Project>>
     */
    #[Computed]
    public function projectDueDates(): SupportCollection
    {
        return $this->projects
            ->filter(fn (Project $project): bool => $project->due_on !== null)
            ->groupBy(fn (Project $project): string => $project->due_on->toDateString());
    }

    public function changeMonth(int $offset): void
    {
        $this->month = $this->calendarMonth()->addMonthsNoOverflow($offset)->format('Y-m');
    }

    public function goToToday(): void
    {
        $this->month = '';
    }

    public function calendarMonth(): Carbon
    {
        return TaskCalendar::month($this->month !== '' ? $this->month : now()->format('Y-m'));
    }

    /**
     * Kanban drop (wire:sort): the task moves to the first status of its
     * project in the category of the target column, at the end of it.
     */
    public function moveTask(int|string $taskId, int $position, int|string|null $category = null): void
    {
        $task = Task::query()
            ->whereNull('parent_id')
            ->whereIn('project_id', $this->visibleProjects->modelKeys())
            ->with('status')
            ->findOrFail((int) $taskId);

        unset($this->tasks);

        $target = StatusCategory::tryFrom((string) $category);

        if ($target === null || $task->status->category === $target) {
            return;
        }

        if (auth()->user()->cannot('update', $task)) {
            $this->toast()->warning('No puedes mover esta tarea', 'Solo la mueven los admins y quienes la tienen asignada.')->send();

            return;
        }

        $status = ProjectStatus::query()
            ->where('project_id', $task->project_id)
            ->where('category', $target)
            ->orderBy('position')
            ->first();

        if ($status === null) {
            $this->toast()->warning('No se pudo mover', 'El proyecto no tiene un estado «'.$target->label().'».')->send();

            return;
        }

        app(MoveTask::class)->handle($task, auth()->user(), $status, null, null);
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    #[Computed]
    public function projectOptions(): array
    {
        return $this->visibleProjects
            ->map(fn (Project $project): array => ['label' => trim($project->icon.' '.$project->name), 'value' => $project->id])
            ->values()
            ->all();
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    #[Computed]
    public function clientOptions(): array
    {
        return Client::query()
            ->whereKey($this->visibleProjects->pluck('client_id')->filter()->unique()->all())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Client $client): array => ['label' => $client->name, 'value' => $client->id])
            ->all();
    }

    /**
     * Team members of the visible projects: the only possible assignees.
     *
     * @return list<array{label: string, value: int}>
     */
    #[Computed]
    public function assigneeOptions(): array
    {
        return User::query()
            ->whereHas('projects', fn (Builder $query) => $query->whereIn('projects.id', $this->visibleProjects->modelKeys()))
            ->orderBy('name')
            ->get(['users.id', 'users.name'])
            ->map(fn (User $user): array => ['label' => $user->name, 'value' => $user->id])
            ->all();
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    #[Computed]
    public function categoryOptions(): array
    {
        return array_map(
            fn (StatusCategory $category): array => ['label' => $category->label(), 'value' => $category->value],
            StatusCategory::cases(),
        );
    }

    public function render(): View
    {
        return view('livewire.tasks.index');
    }

    /**
     * @param  array<int, mixed>  $values
     * @return list<int>
     */
    private function ids(array $values): array
    {
        return array_values(array_unique(array_map('intval', array_filter($values, 'is_numeric'))));
    }

    private function date(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
