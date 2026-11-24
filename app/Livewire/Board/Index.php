<?php

namespace App\Livewire\Board;

use App\Actions\Board\CreateBoardEntry;
use App\Actions\Board\DeleteBoardEntry;
use App\Actions\Board\MarkBoardEntry;
use App\Actions\Board\ReorderBoardProject;
use App\Actions\Board\UpdateBoardEntry;
use App\Actions\Projects\SetProjectPause;
use App\Enums\BoardEntryStatus;
use App\Enums\Permission;
use App\Enums\ProjectPriorityLevel;
use App\Livewire\Concerns\WithCurrentWorkspace;
use App\Models\BoardEntry;
use App\Models\BoardProjectHide;
use App\Models\Project;
use App\Models\Task;
use Carbon\Exceptions\InvalidFormatException;
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
 * Shared workspace board: one row per visible project, one column per day
 * of the selected week (E11). Notes, priority and pause are shared; hiding
 * a row is personal. Week and filters live in the URL.
 *
 * Logic lives here; markup lives in resources/views/livewire/board/index.blade.php.
 */
#[Title('Pizarra')]
class Index extends Component
{
    use Interactions, WithCurrentWorkspace;

    /**
     * Monday of the visible week, as Y-m-d.
     */
    #[Url(as: 'semana')]
    public string $week = '';

    #[Url(as: 'fin_de_semana', except: false)]
    public bool $weekend = false;

    #[Url(as: 'sin_pausados', except: false)]
    public bool $hidePaused = false;

    #[Url(as: 'vencidos', except: false)]
    public bool $onlyOverdue = false;

    #[Url(as: 'sin_trabajo', except: false)]
    public bool $idleToday = false;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public bool $showHidden = false;

    /**
     * Short weekday names, indexed by Carbon's day of week.
     *
     * @var array<int, string>
     */
    private const WEEKDAYS = [
        Carbon::MONDAY => 'Lun',
        Carbon::TUESDAY => 'Mar',
        Carbon::WEDNESDAY => 'Mié',
        Carbon::THURSDAY => 'Jue',
        Carbon::FRIDAY => 'Vie',
        Carbon::SATURDAY => 'Sáb',
        Carbon::SUNDAY => 'Dom',
    ];

    /**
     * Client users have no board (E10). Everyone else sees the projects they may open.
     */
    public function mount(): void
    {
        $this->authorize('view-projects');

        $this->week = $this->monday()->toDateString();
    }

    public function previousWeek(): void
    {
        $this->week = $this->monday()->subWeek()->toDateString();
    }

    public function nextWeek(): void
    {
        $this->week = $this->monday()->addWeek()->toDateString();
    }

    public function currentWeek(): void
    {
        $this->week = today()->startOfWeek(Carbon::MONDAY)->toDateString();
    }

    /**
     * Create a note in a cell. Returns false when the text or the day is rejected,
     * so the open editor can stay on screen.
     */
    public function addEntry(string $projectUuid, string $date, string $body, bool $done = false, string $taskUuid = ''): bool
    {
        $project = $this->project($projectUuid);
        $this->authorize('create', [BoardEntry::class, $project]);

        $day = $this->day($date);
        $note = $this->note($body);

        if ($day === null) {
            $this->toast()->warning('Elige un día de la pizarra.')->send();

            return false;
        }

        if ($note === null) {
            $this->toast()->warning('Escribe una nota de hasta 80 caracteres.')->send();

            return false;
        }

        app(CreateBoardEntry::class)->handle(
            $project,
            auth()->user(),
            $day,
            $note,
            $done ? BoardEntryStatus::Done : BoardEntryStatus::Planned,
            $this->task($project, $taskUuid),
        );

        unset($this->rows);

        return true;
    }

    public function updateEntry(string $uuid, string $body, string $taskUuid = ''): void
    {
        $entry = $this->entry($uuid);
        $this->authorize('update', $entry);

        $note = $this->note($body);

        if ($note === null) {
            $this->toast()->warning('Escribe una nota de hasta 80 caracteres.')->send();

            return;
        }

        app(UpdateBoardEntry::class)->handle($entry, $note, $this->task($entry->project, $taskUuid));

        unset($this->rows);
    }

    public function toggleDone(string $uuid): void
    {
        $entry = $this->entry($uuid);
        $this->authorize('update', $entry);

        app(MarkBoardEntry::class)->handle(
            $entry,
            auth()->user(),
            $entry->status === BoardEntryStatus::Done ? BoardEntryStatus::Planned : BoardEntryStatus::Done,
        );

        unset($this->rows);
    }

    public function confirmDelete(string $uuid): void
    {
        $entry = $this->entry($uuid);
        $this->authorize('delete', $entry);

        $this->dialog()
            ->question('¿Eliminar la nota?', '«'.$entry->body.'» se quita de la pizarra.')
            ->confirm('Eliminar', 'deleteEntry', $uuid)
            ->cancel('Cancelar')
            ->send();
    }

    public function deleteEntry(string $uuid): void
    {
        $entry = $this->entry($uuid);
        $this->authorize('delete', $entry);

        app(DeleteBoardEntry::class)->handle($entry);

        unset($this->rows);
    }

    public function setPriority(string $projectUuid, string $priority): void
    {
        $project = $this->project($projectUuid);
        $this->authorize('update', $project);

        $level = ProjectPriorityLevel::tryFrom($priority);
        abort_unless($level instanceof ProjectPriorityLevel, 422);

        $project->update(['priority' => $level]);

        unset($this->rows);
    }

    public function setDueDate(string $projectUuid, ?string $date): void
    {
        $project = $this->project($projectUuid);
        $this->authorize('update', $project);

        $dueOn = null;

        if (filled($date)) {
            $dueOn = $this->day($date);
            abort_unless($dueOn instanceof Carbon, 422);
        }

        $project->update(['due_on' => $dueOn?->toDateString()]);

        unset($this->rows);
    }

    public function togglePause(string $projectUuid): void
    {
        $project = $this->project($projectUuid);
        $this->authorize('update', $project);

        app(SetProjectPause::class)->handle($project, auth()->user(), ! $project->isPaused());

        unset($this->rows);
    }

    /**
     * Place a project at $position among the rows currently on screen.
     *
     * The resulting order is shared. Neighbors are the rows in view, so a
     * filtered board reorders within that filter.
     */
    public function reorderProjects(int|string $projectId, int $position): void
    {
        $project = Project::query()
            ->visibleTo(auth()->user(), $this->workspace)
            ->findOrFail((int) $projectId);

        $this->authorize('update', $project);

        /** @var SupportCollection<int, Project> $shown */
        $shown = $this->rows
            ->map(fn (array $row): Project => $row['project'])
            ->reject(fn (Project $other): bool => $other->id === $project->id)
            ->values();

        $position = max(0, min($position, $shown->count()));

        app(ReorderBoardProject::class)->handle(
            $project,
            $shown->get($position - 1)?->id,
            $shown->get($position)?->id,
        );

        unset($this->rows);
    }

    public function hideProject(string $projectUuid): void
    {
        $project = $this->project($projectUuid);
        $this->authorize('view', $project);

        BoardProjectHide::query()->firstOrCreate([
            'user_id' => auth()->id(),
            'project_id' => $project->id,
        ]);

        unset($this->rows, $this->hiddenProjects);

        $this->toast()->success('Proyecto oculto', 'Solo deja de verse en tu pizarra.')->send();
    }

    public function showProject(string $projectUuid): void
    {
        $project = $this->project($projectUuid);
        $this->authorize('view', $project);

        BoardProjectHide::query()
            ->where('user_id', auth()->id())
            ->where('project_id', $project->id)
            ->delete();

        unset($this->rows, $this->hiddenProjects);
    }

    /**
     * Monday of the week in the URL, or of today when the value is empty or invalid.
     */
    public function monday(): Carbon
    {
        try {
            $date = $this->week !== '' ? Carbon::parse($this->week) : today();
        } catch (InvalidFormatException) {
            $date = today();
        }

        return $date->startOfWeek(Carbon::MONDAY)->startOfDay();
    }

    /**
     * @return SupportCollection<int, Carbon>
     */
    #[Computed]
    public function days(): SupportCollection
    {
        $start = $this->monday();
        $count = $this->weekend ? 7 : 5;

        return collect(range(0, $count - 1))
            ->map(fn (int $offset): Carbon => $start->copy()->addDays($offset));
    }

    #[Computed]
    public function weekLabel(): string
    {
        $start = $this->days->first();
        $end = $this->days->last();

        return $start->translatedFormat('j M').' – '.$end->translatedFormat('j M Y');
    }

    /**
     * Shared order, pause, priority and due date: whoever may edit the projects.
     */
    #[Computed]
    public function canReorder(): bool
    {
        $user = auth()->user();

        return $user->ownsWorkspace($this->workspace)
            || $user->hasWorkspacePermission($this->workspace, Permission::ProjectsUpdate);
    }

    /**
     * Notes: admins and members of the projects on screen, and the super admin.
     * The list only contains projects the user may open, so one check covers every row.
     */
    #[Computed]
    public function canEditNotes(): bool
    {
        $user = auth()->user();

        return $user->ownsWorkspace($this->workspace)
            || $user->hasWorkspacePermission($this->workspace, Permission::BoardUpdate);
    }

    /**
     * Projects the user hid, so they can show them again.
     *
     * @return Collection<int, Project>
     */
    #[Computed]
    public function hiddenProjects(): Collection
    {
        return Project::query()
            ->visibleTo(auth()->user(), $this->workspace)
            ->whereHas('boardHides', fn (Builder $query) => $query->where('user_id', auth()->id()))
            ->orderBy('name')
            ->get(['id', 'uuid', 'name', 'icon', 'workspace_id']);
    }

    /**
     * One row per project still on the board after the active filters.
     *
     * @return SupportCollection<int, array{
     *     project: Project,
     *     overdueEntries: int,
     *     openTasks: int,
     *     choices: list<array{value: string, label: string}>,
     *     cells: array<string, array{entries: SupportCollection<int, BoardEntry>, idle: bool}>,
     *     tasks: list<array{task: Task, overdue: bool, cells: array<string, SupportCollection<int, BoardEntry>>}>
     * }>
     */
    #[Computed]
    public function rows(): SupportCollection
    {
        $projects = Project::query()
            ->visibleTo(auth()->user(), $this->workspace)
            ->whereDoesntHave('boardHides', fn (Builder $query) => $query->where('user_id', auth()->id()))
            ->when($this->hidePaused, fn (Builder $query) => $query->whereNull('paused_at'))
            ->search($this->search)
            ->with([
                'rootTasks' => fn ($query) => $query
                    ->select('id', 'uuid', 'project_id', 'name', 'position', 'ends_on', 'completed_at')
                    ->whereNull('completed_at')
                    ->orderBy('position')
                    ->with([
                        'subtasks' => fn ($query) => $query
                            ->select('id', 'uuid', 'project_id', 'parent_id', 'name')
                            ->orderBy('position'),
                    ]),
            ])
            ->withCount(['tasks as open_overdue_tasks_count' => fn (Builder $query) => $query->openPastDue()])
            ->onBoard()
            ->get();

        $ids = $projects->modelKeys();
        $today = today()->toDateString();

        $overdueEntries = $ids === [] ? collect() : BoardEntry::query()
            ->whereIn('project_id', $ids)
            ->where('status', BoardEntryStatus::Planned)
            ->where('occurs_on', '<', $today)
            ->selectRaw('project_id, count(*) as aggregate')
            ->groupBy('project_id')
            ->pluck('aggregate', 'project_id');

        $workedToday = $ids === [] ? collect() : BoardEntry::query()
            ->whereIn('project_id', $ids)
            ->where('status', BoardEntryStatus::Done)
            ->where('occurs_on', $today)
            ->pluck('project_id')
            ->flip();

        if ($this->onlyOverdue) {
            $projects = $projects->filter(fn (Project $project): bool => $project->isOverdue()
                || (int) ($overdueEntries[$project->id] ?? 0) > 0
                || (int) $project->open_overdue_tasks_count > 0);
        }

        if ($this->idleToday) {
            $projects = $projects
                ->filter(fn (Project $project): bool => ! $project->isPaused() && ! $workedToday->has($project->id))
                ->values();
        }

        $start = $this->days->first()->toDateString();
        $end = $this->days->last()->toDateString();
        $visibleIds = $projects->modelKeys();

        $entries = $visibleIds === [] ? collect() : BoardEntry::query()
            ->whereIn('project_id', $visibleIds)
            ->whereBetween('occurs_on', [$start, $end])
            ->with(['creator:id,name', 'task:id,uuid,name,parent_id,project_id'])
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->groupBy('project_id');

        return collect($projects->values()->map(function (Project $project) use ($entries, $overdueEntries, $today): array {
            /** @var SupportCollection<int, BoardEntry> $projectEntries */
            $projectEntries = $entries->get($project->id, collect());
            $byDate = $projectEntries->groupBy(fn (BoardEntry $entry): string => $entry->occurs_on->toDateString());

            $rootOf = [];
            $choices = [];

            foreach ($project->rootTasks as $task) {
                $rootOf[$task->id] = $task->id;
                $choices[] = ['value' => $task->uuid, 'label' => $task->name];

                foreach ($task->subtasks as $subtask) {
                    $rootOf[$subtask->id] = $task->id;
                    $choices[] = ['value' => $subtask->uuid, 'label' => '— '.$subtask->name];
                }
            }

            $byRoot = [];

            foreach ($projectEntries as $entry) {
                $rootId = $entry->task_id !== null ? ($rootOf[$entry->task_id] ?? null) : null;

                if ($rootId !== null) {
                    $byRoot[$rootId][$entry->occurs_on->toDateString()][] = $entry;
                }
            }

            $cells = [];

            foreach ($this->days as $day) {
                $key = $day->toDateString();
                $dayEntries = $byDate->get($key, collect());
                $cells[$key] = [
                    'entries' => $dayEntries->values(),
                    'idle' => ! $project->isPaused()
                        && $key <= $today
                        && $dayEntries->doesntContain(fn (BoardEntry $entry): bool => $entry->status === BoardEntryStatus::Done),
                ];
            }

            $tasks = [];

            foreach ($project->rootTasks as $task) {
                $taskCells = [];

                foreach ($this->days as $day) {
                    $key = $day->toDateString();
                    $taskCells[$key] = collect($byRoot[$task->id][$key] ?? [])->values();
                }

                $tasks[] = [
                    'task' => $task,
                    'overdue' => $task->ends_on !== null && $task->ends_on->toDateString() < $today,
                    'cells' => $taskCells,
                ];
            }

            return [
                'project' => $project,
                'overdueEntries' => (int) ($overdueEntries[$project->id] ?? 0),
                'openTasks' => (int) $project->open_overdue_tasks_count,
                'choices' => $choices,
                'cells' => $cells,
                'tasks' => $tasks,
            ];
        })->all());
    }

    public function weekday(Carbon $day): string
    {
        return self::WEEKDAYS[$day->dayOfWeek];
    }

    public function render(): View
    {
        return view('livewire.board.index');
    }

    private function project(string $uuid): Project
    {
        return Project::query()
            ->visibleTo(auth()->user(), $this->workspace)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    private function entry(string $uuid): BoardEntry
    {
        $entry = BoardEntry::query()->where('uuid', $uuid)->firstOrFail();

        abort_unless($entry->workspace_id === $this->workspace->id, 404);
        abort_unless(auth()->user()->can('view', $entry->project), 404);

        return $entry;
    }

    private function task(Project $project, string $uuid): ?Task
    {
        if ($uuid === '') {
            return null;
        }

        return Task::query()
            ->where('project_id', $project->id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    private function day(string $date): ?Carbon
    {
        $day = Carbon::createFromFormat('Y-m-d', $date);

        if (! $day instanceof Carbon || $day->format('Y-m-d') !== $date) {
            return null;
        }

        return $day->startOfDay();
    }

    private function note(string $body): ?string
    {
        $note = preg_replace('/\s+/u', ' ', trim($body)) ?? '';

        if ($note === '' || mb_strlen($note) > 80) {
            return null;
        }

        return $note;
    }
}
