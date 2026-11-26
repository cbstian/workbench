<?php

namespace App\Livewire\Portal;

use App\Enums\CommentThread;
use App\Enums\StatusCategory;
use App\Enums\TaskView;
use App\Models\Page;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\Task;
use App\Support\ClientPortal;
use App\Support\CurrentWorkspace;
use App\Support\TaskCalendar;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * A project in the client portal (P-06): its tasks in kanban, list or
 * calendar, read only, and its documentation. A task opens in a side panel
 * with its conversation with the client (`?tarea=uuid`).
 */
class ProjectShow extends Component
{
    /**
     * Tab showing the project's documentation instead of a task view.
     */
    public const string DOCS = 'docs';

    /**
     * Columns the list may be sorted by.
     *
     * @var list<string>
     */
    public const array LIST_SORTS = ['name', 'status', 'priority', 'ends_on'];

    public Project $project;

    #[Url(as: 'vista')]
    public string $view = 'kanban';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'orden', except: 'ends_on')]
    public string $listSort = 'ends_on';

    #[Url(as: 'dir', except: 'asc')]
    public string $listDirection = 'asc';

    #[Url(as: 'mes', except: '')]
    public string $month = '';

    #[Url(as: 'tarea', except: '')]
    public string $taskUuid = '';

    public bool $showTask = false;

    public function mount(): void
    {
        $this->authorize('viewInPortal', $this->project);

        $currentWorkspace = app(CurrentWorkspace::class);

        if ($currentWorkspace->get()?->id !== $this->project->workspace_id) {
            $currentWorkspace->switchTo($this->project->workspace);
        }

        if (! in_array($this->view, [...array_column(TaskView::cases(), 'value'), self::DOCS], true)) {
            $this->view = TaskView::Kanban->value;
        }

        if ($this->taskUuid !== '') {
            $this->openTask($this->taskUuid);
        }
    }

    /**
     * Root tasks of the project, matching the search.
     *
     * @return Collection<int, Task>
     */
    #[Computed]
    public function tasks(): Collection
    {
        return $this->project->rootTasks()
            ->when(trim($this->search) !== '', fn (Builder $query) => $query->whereLike('name', '%'.trim($this->search).'%'))
            ->with(['status', 'priority', 'assignees:id,name', 'labels:id,name,color'])
            ->withCount([
                'subtasks',
                'subtasks as closed_subtasks_count' => fn (Builder $query) => $query->whereNotNull('completed_at'),
                'comments as client_comments_count' => fn (Builder $query) => $query->where('thread', CommentThread::Client),
            ])
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    /**
     * Kanban columns: every status of the project, cancelled ones only when
     * they hold tasks.
     *
     * @return Collection<int, ProjectStatus>
     */
    #[Computed]
    public function columns(): Collection
    {
        $tasksByStatus = $this->tasks->groupBy('status_id');

        return $this->project->statuses()->get()
            ->reject(fn (ProjectStatus $status): bool => $status->category === StatusCategory::Cancelled && ! $tasksByStatus->has($status->id))
            ->each(fn (ProjectStatus $status) => $status->setRelation('tasks', $tasksByStatus->get($status->id, new Collection)))
            ->values();
    }

    /**
     * Tasks sorted for the list; closed ones go last.
     *
     * @return Collection<int, Task>
     */
    #[Computed]
    public function listTasks(): Collection
    {
        $descending = $this->listDirection === 'desc';

        $sorted = match ($this->listSort) {
            'name' => $this->tasks->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE, $descending),
            'status' => $this->tasks->sortBy(fn (Task $task): int => $task->status->position, descending: $descending),
            'priority' => $this->tasks->sortBy(fn (Task $task): int => $task->priority->position, descending: $descending),
            default => $this->tasks->sortBy(fn (Task $task): string => $task->ends_on?->toDateString() ?? ($descending ? '0000' : '9999'), descending: $descending),
        };

        return $sorted->sortBy(fn (Task $task): int => $task->completed_at !== null ? 1 : 0)->values();
    }

    /**
     * Totals of the project header.
     *
     * @return array{total: int, closed: int, overdue: int}
     */
    #[Computed]
    public function progress(): array
    {
        $tasks = $this->project->rootTasks();

        return [
            'total' => (clone $tasks)->count(),
            'closed' => (clone $tasks)->whereNotNull('completed_at')->count(),
            'overdue' => (clone $tasks)->openPastDue()->count(),
        ];
    }

    #[Computed]
    public function calendarMonth(): Carbon
    {
        return TaskCalendar::month($this->month);
    }

    /**
     * @return list<list<array{date: Carbon, inMonth: bool, isToday: bool, tasks: \Illuminate\Support\Collection<int, Task>}>>
     */
    #[Computed]
    public function calendarWeeks(): array
    {
        return TaskCalendar::weeks($this->calendarMonth, $this->tasks);
    }

    #[Computed]
    public function undatedCount(): int
    {
        return TaskCalendar::undatedCount($this->tasks);
    }

    /**
     * Documentation of the project shared with the client.
     *
     * @return Collection<int, Page>
     */
    #[Computed]
    public function pages(): Collection
    {
        return app(ClientPortal::class)->visiblePages($this->project->workspace, $this->project);
    }

    #[Computed]
    public function openedTask(): ?Task
    {
        if ($this->taskUuid === '') {
            return null;
        }

        return $this->project->tasks()->where('uuid', $this->taskUuid)->first();
    }

    public function setView(string $view): void
    {
        $this->view = in_array($view, [...array_column(TaskView::cases(), 'value'), self::DOCS], true) ? $view : TaskView::Kanban->value;
    }

    public function sortList(string $column): void
    {
        if (! in_array($column, self::LIST_SORTS, true)) {
            return;
        }

        $this->listDirection = $this->listSort === $column && $this->listDirection === 'asc' ? 'desc' : 'asc';
        $this->listSort = $column;
        unset($this->listTasks);
    }

    public function changeMonth(int $offset): void
    {
        $this->month = $this->calendarMonth->copy()->addMonthsNoOverflow(max(-12, min(12, $offset)))->format('Y-m');
        unset($this->calendarMonth, $this->calendarWeeks);
    }

    public function goToToday(): void
    {
        $this->month = '';
        unset($this->calendarMonth, $this->calendarWeeks);
    }

    /**
     * Open a task or subtask of the project in the side panel.
     */
    #[On('portal-open-task')]
    public function openTask(string $uuid): void
    {
        $task = $this->project->tasks()->where('uuid', $uuid)->first();

        if ($task === null || auth()->user()->cannot('viewInPortal', $task)) {
            $this->closeTask();

            return;
        }

        $this->taskUuid = $task->uuid;
        $this->showTask = true;
        unset($this->openedTask);
    }

    public function closeTask(): void
    {
        $this->taskUuid = '';
        $this->showTask = false;
        unset($this->openedTask);
    }

    public function updatedShowTask(bool $value): void
    {
        if (! $value) {
            $this->closeTask();
        }
    }

    public function updatedSearch(): void
    {
        unset($this->tasks, $this->columns, $this->listTasks, $this->calendarWeeks, $this->undatedCount);
    }

    public function render(): View
    {
        return view('livewire.portal.project-show')->title($this->project->name);
    }
}
