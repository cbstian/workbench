<?php

namespace App\Livewire\Projects;

use App\Actions\Tasks\MoveTask;
use App\Actions\Tasks\StoreTaskAttachment;
use App\Enums\TaskView;
use App\Livewire\Concerns\WithTaskOptions;
use App\Livewire\Forms\TaskForm;
use App\Livewire\Tasks\Attachments;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\ProjectTaskView;
use App\Models\Task;
use App\Support\TaskCalendar;
use App\Support\TaskFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use TallStackUi\Traits\Interactions;

/**
 * Tasks of a project in three views — kanban, list and calendar — with
 * filters (F-08, RN-11). The view and the filters are remembered per user and
 * project in project_task_views, so they are still active on return.
 *
 * The same filtered root tasks feed the three views. In the kanban a task is
 * dragged to another status or position (MoveTask). Clicking a task in any
 * view opens it in the same slide used to create one, with its attachments.
 */
class TaskBoard extends Component
{
    use Interactions, WithFileUploads, WithTaskOptions;

    /**
     * Columns the list view may sort by.
     *
     * @var list<string>
     */
    public const array LIST_SORTS = ['position', 'name', 'status', 'priority', 'ends_on'];

    #[Locked]
    public Project $project;

    public string $view = 'kanban';

    /**
     * @var list<int>
     */
    public array $statusIds = [];

    /**
     * @var list<int>
     */
    public array $priorityIds = [];

    /**
     * @var list<int>
     */
    public array $assigneeIds = [];

    /**
     * @var list<int>
     */
    public array $labelIds = [];

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public string $search = '';

    /**
     * Month shown in the calendar (Y-m). Not remembered.
     */
    public string $month = '';

    public string $listSort = 'position';

    public string $listDirection = 'asc';

    public TaskForm $form;

    public bool $showTaskForm = false;

    /**
     * Task open in the slide, or null while creating one.
     */
    #[Locked]
    public ?int $editingTaskId = null;

    /**
     * Bumped on every opening of the slide, so the description editor and the
     * attachments are replaced instead of keeping the previous task.
     */
    #[Locked]
    public int $taskFormVersion = 0;

    /**
     * Files picked while creating a task, stored once it exists.
     *
     * @var list<TemporaryUploadedFile>
     */
    public array $pendingUploads = [];

    public function mount(): void
    {
        $this->authorize('view', $this->project);

        $saved = ProjectTaskView::query()
            ->where('user_id', auth()->id())
            ->where('project_id', $this->project->id)
            ->first();

        $this->view = $saved?->view->value ?? TaskView::Kanban->value;
        $this->fillFilters(TaskFilters::fromArray($saved->filters ?? [], $this->project));
        $this->month = now()->format('Y-m');
    }

    protected function taskOptionsProject(): Project
    {
        return $this->project;
    }

    /**
     * Remember the view and the filters whenever one of them changes.
     */
    public function updated(string $property): void
    {
        if ($property === 'view' && TaskView::tryFrom($this->view) === null) {
            $this->view = TaskView::Kanban->value;
        }

        if (in_array(explode('.', $property)[0], ['view', 'statusIds', 'priorityIds', 'assigneeIds', 'labelIds', 'dateFrom', 'dateTo', 'search'], true)) {
            $this->remember();
        }
    }

    public function clearFilters(): void
    {
        $this->fillFilters(new TaskFilters);
        $this->remember();
    }

    /**
     * Only show tasks assigned to the signed-in user.
     */
    public function onlyMine(): void
    {
        $this->assigneeIds = [auth()->id()];
        $this->remember();
    }

    #[Computed]
    public function filters(): TaskFilters
    {
        return TaskFilters::fromArray([
            'status_ids' => $this->statusIds,
            'priority_ids' => $this->priorityIds,
            'assignee_ids' => $this->assigneeIds,
            'label_ids' => $this->labelIds,
            'date_range' => ['from' => $this->dateFrom, 'to' => $this->dateTo],
            'search' => $this->search,
        ], $this->project);
    }

    /**
     * Root tasks after the filters, the source of the three views.
     *
     * @return Collection<int, Task>
     */
    #[Computed]
    public function tasks(): Collection
    {
        return $this->filters->apply($this->project->rootTasks()->getQuery())
            ->with(['status', 'priority', 'assignees:id,name', 'labels:id,name,color'])
            ->withCount(['subtasks', 'subtasks as closed_subtasks_count' => fn (Builder $query) => $query->whereNotNull('completed_at')])
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    /**
     * Kanban columns: every status, or only the filtered ones.
     *
     * @return Collection<int, ProjectStatus>
     */
    #[Computed]
    public function columns(): Collection
    {
        return $this->project->statuses()
            ->when($this->filters->statusIds !== [], fn ($query) => $query->whereKey($this->filters->statusIds))
            ->get();
    }

    /**
     * Tasks of the list view in the chosen order.
     *
     * @return \Illuminate\Support\Collection<int, Task>
     */
    #[Computed]
    public function listTasks(): \Illuminate\Support\Collection
    {
        $sort = in_array($this->listSort, self::LIST_SORTS, true) ? $this->listSort : 'position';

        $sorted = match ($sort) {
            'name' => $this->tasks->sortBy(fn (Task $task): string => mb_strtolower($task->name)),
            'status' => $this->tasks->sortBy(fn (Task $task): array => [$task->status->position, $task->position]),
            'priority' => $this->tasks->sortBy(fn (Task $task): array => [$task->priority->position, $task->position]),
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
     * Weeks (Monday first) of the calendar month, each a list of days with
     * the tasks whose dates cover that day.
     *
     * @return list<list<array{date: Carbon, inMonth: bool, isToday: bool, tasks: \Illuminate\Support\Collection<int, Task>}>>
     */
    #[Computed]
    public function calendarWeeks(): array
    {
        return TaskCalendar::weeks($this->calendarMonth(), $this->tasks);
    }

    /**
     * Filtered tasks without dates, which the calendar cannot place.
     */
    #[Computed]
    public function undatedCount(): int
    {
        return TaskCalendar::undatedCount($this->tasks);
    }

    public function changeMonth(int $offset): void
    {
        $this->month = $this->calendarMonth()->addMonthsNoOverflow($offset)->format('Y-m');
        unset($this->calendarWeeks);
    }

    public function goToToday(): void
    {
        $this->month = now()->format('Y-m');
        unset($this->calendarWeeks);
    }

    public function calendarMonth(): Carbon
    {
        return TaskCalendar::month($this->month);
    }

    #[Computed]
    public function canCreateTasks(): bool
    {
        return auth()->user()->can('create', [Task::class, $this->project]);
    }

    /**
     * Open the new task form, optionally in a kanban column. A team member
     * starts as its assignee, so a member can keep editing it (RN-23).
     */
    public function createTask(?int $statusId = null): void
    {
        $this->authorize('create', [Task::class, $this->project]);

        $this->form->setProject($this->project);
        $this->form->resetValidation();
        $this->resetValidation('pendingUploads');
        $this->editingTaskId = null;
        $this->pendingUploads = [];
        $this->taskFormVersion++;
        unset($this->editingTask);

        if ($statusId !== null && $this->project->statuses()->whereKey($statusId)->exists()) {
            $this->form->status_id = $statusId;
        }

        if (auth()->user()->isOnTeamOf($this->project)) {
            $this->form->assignee_ids = [auth()->id()];
        }

        $this->showTaskForm = true;
    }

    /**
     * Open a task of the project in the slide. Whoever sees it may open it;
     * only those who may update it get editable fields (A-17).
     */
    public function editTask(int $taskId): void
    {
        $task = $this->project->tasks()->findOrFail($taskId);
        $this->authorize('view', $task);

        $this->form->setTask($task);
        $this->form->resetValidation();
        $this->editingTaskId = $task->id;
        $this->pendingUploads = [];
        $this->taskFormVersion++;
        unset($this->editingTask);

        $this->showTaskForm = true;
    }

    #[Computed]
    public function editingTask(): ?Task
    {
        return $this->editingTaskId === null ? null : $this->project->tasks()->find($this->editingTaskId);
    }

    #[Computed]
    public function canEditTask(): bool
    {
        return $this->editingTask !== null && auth()->user()->can('update', $this->editingTask);
    }

    public function saveTask(): void
    {
        if ($this->editingTaskId !== null) {
            $this->updateTask();

            return;
        }

        $this->authorize('create', [Task::class, $this->project]);

        $this->validate(
            ['pendingUploads' => ['array', 'max:10'], 'pendingUploads.*' => ['file', 'max:'.Attachments::MAX_KILOBYTES]],
            ['pendingUploads.*.max' => 'Cada archivo puede pesar hasta 10 MB.'],
            ['pendingUploads' => 'adjuntos', 'pendingUploads.*' => 'adjunto'],
        );

        $task = $this->form->store(auth()->user());
        $attached = $this->storePendingUploads($task);

        $this->showTaskForm = false;
        unset($this->tasks);
        $this->toast()->success('Tarea creada', "«{$task->name}» quedó en {$task->status->name}".match ($attached) {
            0 => '.',
            1 => ', con 1 adjunto.',
            default => ", con {$attached} adjuntos.",
        })->send();
    }

    /**
     * Remove a file picked for the new task (x-upload delete button).
     *
     * @param  array{temporary_name: string}  $content
     */
    public function deleteUpload(array $content): void
    {
        $this->pendingUploads = array_values(array_filter(
            $this->pendingUploads,
            function (TemporaryUploadedFile $upload) use ($content): bool {
                if ($upload->getFilename() !== $content['temporary_name']) {
                    return true;
                }

                rescue(fn () => $upload->delete(), report: false);

                return false;
            },
        ));
    }

    private function updateTask(): void
    {
        $task = $this->editingTask ?? abort(404);
        $this->authorize('update', $task);

        $this->form->update(auth()->user());

        $this->showTaskForm = false;
        unset($this->tasks, $this->editingTask);
        $this->toast()->success('Tarea guardada')->send();
    }

    /**
     * Store the files picked while creating, if the creator may attach to the
     * new task. Returns how many were stored.
     */
    private function storePendingUploads(Task $task): int
    {
        $uploads = $this->pendingUploads;
        $this->pendingUploads = [];

        if ($uploads === [] || auth()->user()->cannot('collaborate', $task)) {
            return 0;
        }

        foreach ($uploads as $upload) {
            app(StoreTaskAttachment::class)->handle($task, auth()->user(), $upload);
        }

        return count($uploads);
    }

    /**
     * Kanban drop (wire:sort): the task lands at `$position` of the column of
     * `$statusId`, counted among the tasks shown there.
     */
    public function moveTask(int|string $taskId, int $position, int|string|null $statusId = null): void
    {
        $task = $this->project->rootTasks()->findOrFail((int) $taskId);

        if (auth()->user()->cannot('update', $task)) {
            unset($this->tasks);
            $this->toast()->warning('No puedes mover esta tarea', 'Solo la mueven los admins y quienes la tienen asignada.')->send();

            return;
        }

        $status = $this->project->statuses()->findOrFail((int) ($statusId ?? $task->status_id));
        $shown = $this->tasks->where('status_id', $status->id)->reject(fn (Task $other): bool => $other->id === $task->id)->values();
        $position = max(0, min($position, $shown->count()));

        app(MoveTask::class)->handle(
            $task,
            auth()->user(),
            $status,
            $shown->get($position - 1)?->id,
            $shown->get($position)?->id,
        );

        unset($this->tasks);
    }

    private function fillFilters(TaskFilters $filters): void
    {
        $this->statusIds = $filters->statusIds;
        $this->priorityIds = $filters->priorityIds;
        $this->assigneeIds = $filters->assigneeIds;
        $this->labelIds = $filters->labelIds;
        $this->dateFrom = $filters->from;
        $this->dateTo = $filters->to;
        $this->search = $filters->search ?? '';
        unset($this->filters, $this->tasks);
    }

    private function remember(): void
    {
        unset($this->filters, $this->tasks);

        ProjectTaskView::query()->updateOrCreate(
            ['user_id' => auth()->id(), 'project_id' => $this->project->id],
            ['view' => TaskView::tryFrom($this->view) ?? TaskView::Kanban, 'filters' => $this->filters->toArray()],
        );
    }

    public function render(): View
    {
        return view('livewire.projects.task-board');
    }
}
