<?php

use App\Enums\TaskActivityEvent;
use App\Enums\TaskView;
use App\Enums\WorkspaceRole;
use App\Livewire\Projects\TaskBoard;
use App\Models\Project;
use App\Models\ProjectTaskView;
use App\Models\Task;
use App\Models\User;
use Livewire\Livewire;

/**
 * @return array{0: User, 1: Project}
 */
function boardProject(): array
{
    [$owner, $workspace] = superAdminWithWorkspace();

    return [$owner, Project::factory()->for($workspace)->create()];
}

it('shows the same tasks in the three views (F-08)', function (string $view) {
    [$owner, $project] = boardProject();
    Task::factory()->for($project)->create(['name' => 'Tarea con fecha', 'starts_on' => now()->startOfMonth()->addDays(2), 'ends_on' => now()->startOfMonth()->addDays(4)]);

    Livewire::actingAs($owner)
        ->test(TaskBoard::class, ['project' => $project])
        ->set('view', $view)
        ->assertSeeText('Tarea con fecha');
})->with(['kanban', 'list', 'calendar']);

it('renders the board inside the project page', function () {
    [$owner, $project] = boardProject();

    $this->actingAs($owner)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertSeeLivewire(TaskBoard::class)
        ->assertSeeText('Kanban')
        ->assertSeeText('Calendario');
});

it('filters by status, priority, assignee, label, dates and search', function () {
    [$owner, $project] = boardProject();
    $member = User::factory()->memberOf($project->workspace)->create();
    $project->members()->attach($member);
    $label = $project->taskLabels()->create(['name' => 'Bug']);
    $doing = $project->statuses()->where('name', 'En curso')->sole();
    $urgent = $project->priorities()->where('name', 'urgente')->sole();

    $match = Task::factory()->for($project)->create([
        'name' => 'Corregir facturación', 'status_id' => $doing->id, 'priority_id' => $urgent->id,
        'starts_on' => '2026-11-10', 'ends_on' => '2026-11-20',
    ]);
    $match->assignees()->attach($member);
    $match->labels()->attach($label);
    Task::factory()->for($project)->create(['name' => 'Otra cosa']);

    $component = Livewire::actingAs($owner)->test(TaskBoard::class, ['project' => $project])->set('view', 'list');

    foreach ([
        ['statusIds', [$doing->id]],
        ['priorityIds', [$urgent->id]],
        ['assigneeIds', [$member->id]],
        ['labelIds', [$label->id]],
        ['dateFrom', '2026-11-15'],
        ['search', 'facturacion'],
    ] as [$property, $value]) {
        $component->call('clearFilters')->set($property, $value)
            ->assertSeeText('Corregir facturación')
            ->assertDontSeeText('Otra cosa');
    }

    $component->call('clearFilters')->set('dateTo', '2026-11-05')->assertDontSeeText('Corregir facturación');
});

it('remembers the view and filters per user and project (RN-11)', function () {
    [$owner, $project] = boardProject();
    $doing = $project->statuses()->where('name', 'En curso')->value('id');

    Livewire::actingAs($owner)->test(TaskBoard::class, ['project' => $project])
        ->set('view', 'calendar')
        ->set('statusIds', [$doing])
        ->set('search', 'pagos');

    $saved = ProjectTaskView::query()->where('user_id', $owner->id)->where('project_id', $project->id)->sole();

    expect($saved->view)->toBe(TaskView::Calendar)
        ->and($saved->filters['status_ids'])->toBe([$doing])
        ->and($saved->filters['search'])->toBe('pagos');

    Livewire::actingAs($owner)->test(TaskBoard::class, ['project' => $project])
        ->assertSet('view', 'calendar')
        ->assertSet('statusIds', [$doing])
        ->assertSet('search', 'pagos');

    $other = User::factory()->memberOf($project->workspace)->create();
    $project->members()->attach($other);

    Livewire::actingAs($other)->test(TaskBoard::class, ['project' => $project])
        ->assertSet('view', 'kanban')
        ->assertSet('statusIds', []);
});

it('ignores stored ids that no longer belong to the project', function () {
    [$owner, $project] = boardProject();
    $doing = $project->statuses()->where('name', 'En curso')->sole();
    ProjectTaskView::factory()->create([
        'user_id' => $owner->id,
        'project_id' => $project->id,
        'view' => TaskView::List,
        'filters' => ['status_ids' => [$doing->id, 999999], 'assignee_ids' => [888888]],
    ]);
    $doing->delete();

    Livewire::actingAs($owner)->test(TaskBoard::class, ['project' => $project])
        ->assertSet('view', 'list')
        ->assertSet('statusIds', [])
        ->assertSet('assigneeIds', []);
});

it('moves a task to another column and position, recording the status change', function () {
    [$owner, $project] = boardProject();
    $todo = $project->statuses()->where('name', 'Por hacer')->sole();
    $first = Task::factory()->for($project)->create(['status_id' => $todo->id, 'position' => 1024]);
    $second = Task::factory()->for($project)->create(['status_id' => $todo->id, 'position' => 2048]);
    $moved = Task::factory()->for($project)->create(['position' => 1024]);

    Livewire::actingAs($owner)->test(TaskBoard::class, ['project' => $project])
        ->call('moveTask', $moved->id, 1, $todo->id);

    $moved->refresh();

    expect($moved->status_id)->toBe($todo->id)
        ->and($moved->position)->toBeGreaterThan($first->position)->toBeLessThan($second->position)
        ->and($moved->activities()->where('event', TaskActivityEvent::StatusChanged)->sole()->changes['status']['new']['name'])->toBe('Por hacer');
});

it('rebalances the column when two neighbors are contiguous', function () {
    [$owner, $project] = boardProject();
    $first = Task::factory()->for($project)->create(['position' => 1]);
    $second = Task::factory()->for($project)->create(['position' => 2]);
    $moved = Task::factory()->for($project)->create(['position' => 5000]);

    Livewire::actingAs($owner)->test(TaskBoard::class, ['project' => $project])
        ->call('moveTask', $moved->id, 1, $project->initialStatus->id);

    expect(Task::query()->whereKey([$first->id, $moved->id, $second->id])->orderBy('position')->pluck('id')->all())
        ->toBe([$first->id, $moved->id, $second->id]);
});

it('does not let a member move a task that is not assigned to them (A-17)', function () {
    [, $project] = boardProject();
    $member = User::factory()->memberOf($project->workspace)->create();
    $project->members()->attach($member);
    $task = Task::factory()->for($project)->create();
    $done = $project->statuses()->where('name', 'Hecho')->sole();

    Livewire::actingAs($member)->test(TaskBoard::class, ['project' => $project])
        ->call('moveTask', $task->id, 0, $done->id);

    expect($task->fresh()->status_id)->toBe($project->initialStatus->id);
});

it('opens the new task form in a kanban column', function () {
    [, $project] = boardProject();
    $admin = User::factory()->memberOf($project->workspace, WorkspaceRole::Admin)->create();
    $project->members()->attach($admin);
    $doing = $project->statuses()->where('name', 'En curso')->value('id');

    Livewire::actingAs($admin)->test(TaskBoard::class, ['project' => $project])
        ->call('createTask', $doing)
        ->assertSet('form.status_id', $doing)
        ->assertSet('showTaskForm', true);
});

it('navigates the calendar months', function () {
    $this->travelTo(now()->setDate(2026, 11, 21));
    [$owner, $project] = boardProject();

    Livewire::actingAs($owner)->test(TaskBoard::class, ['project' => $project])
        ->set('view', 'calendar')
        ->assertSeeText('noviembre 2026')
        ->call('changeMonth', 1)
        ->assertSeeText('diciembre 2026')
        ->call('goToToday')
        ->assertSet('month', '2026-11');
});
