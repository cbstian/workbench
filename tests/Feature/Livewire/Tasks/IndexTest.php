<?php

use App\Enums\TaskActivityEvent;
use App\Enums\WorkspaceRole;
use App\Livewire\Tasks\Index;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Livewire\Livewire;

it('forbids a client user from opening the task overview', function () {
    [, $workspace] = superAdminWithWorkspace();
    $clientUser = User::factory()->memberOf($workspace, WorkspaceRole::Client)->create();

    $this->actingAs($clientUser)
        ->get(route('tasks.index'))
        ->assertForbidden();
});

it('shows the tasks of every project the user may open in the three views', function (string $view) {
    [, $workspace] = superAdminWithWorkspace();
    $member = User::factory()->memberOf($workspace)->create();
    $first = Project::factory()->for($workspace)->create(['name' => 'Portal web']);
    $second = Project::factory()->for($workspace)->create(['name' => 'App móvil']);
    $foreign = Project::factory()->for($workspace)->create();
    $first->members()->attach($member);
    $second->members()->attach($member);
    $dates = ['starts_on' => today(), 'ends_on' => today()];
    Task::factory()->for($first)->create(['name' => 'Tarea del portal', ...$dates]);
    Task::factory()->for($second)->create(['name' => 'Tarea de la app', ...$dates]);
    Task::factory()->for($foreign)->create(['name' => 'Tarea ajena', ...$dates]);
    Task::factory()->for(Project::factory()->create())->create(['name' => 'Otro espacio', ...$dates]);

    Livewire::actingAs($member)
        ->test(Index::class)
        ->set('view', $view)
        ->assertSeeText('Tarea del portal')
        ->assertSeeText('Tarea de la app')
        ->assertDontSeeText('Tarea ajena')
        ->assertDontSeeText('Otro espacio');
})->with(['kanban', 'list', 'calendar']);

it('renders the page and its sidebar link', function () {
    [$owner] = superAdminWithWorkspace();

    $this->actingAs($owner)
        ->get(route('tasks.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class)
        ->assertSee(route('tasks.index'), escape: false);
});

it('hides closed tasks unless asked and filters by status category', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create();
    $done = $project->statuses()->where('name', 'Hecho')->sole();
    $doing = $project->statuses()->where('name', 'En curso')->sole();
    Task::factory()->for($project)->create(['name' => 'Tarea cerrada', 'status_id' => $done->id]);
    Task::factory()->for($project)->create(['name' => 'Tarea en curso', 'status_id' => $doing->id]);
    Task::factory()->for($project)->create(['name' => 'Tarea en backlog']);

    Livewire::actingAs($owner)->test(Index::class)
        ->set('view', 'list')
        ->assertSeeText('Tarea en curso')
        ->assertDontSeeText('Tarea cerrada')
        ->set('showClosed', true)
        ->assertSeeText('Tarea cerrada')
        ->set('categories', ['started'])
        ->assertSeeText('Tarea en curso')
        ->assertDontSeeText('Tarea en backlog')
        ->assertDontSeeText('Tarea cerrada');
});

it('filters by project, client, assignee, dates, overdue and paused projects', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $client = Client::factory()->for($workspace)->create();
    $withClient = Project::factory()->for($workspace)->create(['client_id' => $client->id]);
    $other = Project::factory()->for($workspace)->create();
    $paused = Project::factory()->for($workspace)->paused()->create();
    $member = User::factory()->memberOf($workspace)->create();
    $withClient->members()->attach($member);

    $mine = Task::factory()->for($withClient)->create(['name' => 'Tarea asignada', 'starts_on' => today()->addDays(10), 'ends_on' => today()->addDays(12)]);
    $mine->assignees()->attach($member);
    Task::factory()->for($other)->create(['name' => 'Tarea vencida', 'ends_on' => today()->subDays(3)]);
    Task::factory()->for($paused)->create(['name' => 'Tarea pausada']);

    $component = Livewire::actingAs($owner)->test(Index::class)->set('view', 'list');

    $component->assertSeeText($withClient->name);

    $component->set('projectIds', [$withClient->id])
        ->assertSeeText('Tarea asignada')->assertDontSeeText('Tarea vencida')
        ->call('clearFilters');

    $component->set('clientIds', [$client->id])
        ->assertSeeText('Tarea asignada')->assertDontSeeText('Tarea pausada')
        ->call('clearFilters');

    $component->set('assigneeIds', [$member->id])
        ->assertSeeText('Tarea asignada')->assertDontSeeText('Tarea vencida')
        ->call('clearFilters');

    $component->set('dateFrom', today()->addDays(11)->toDateString())->set('dateTo', today()->addDays(30)->toDateString())
        ->assertSeeText('Tarea asignada')->assertDontSeeText('Tarea pausada')
        ->call('clearFilters');

    $component->set('onlyOverdue', true)
        ->assertSeeText('Tarea vencida')->assertDontSeeText('Tarea asignada')
        ->call('clearFilters');

    $component->set('hidePaused', true)
        ->assertSeeText('Tarea asignada')->assertDontSeeText('Tarea pausada');
});

it('limits to my tasks', function () {
    [, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create();
    $member = User::factory()->memberOf($workspace)->create();
    $project->members()->attach($member);
    Task::factory()->for($project)->create(['name' => 'Mía'])->assignees()->attach($member);
    Task::factory()->for($project)->create(['name' => 'De nadie']);

    Livewire::actingAs($member)->test(Index::class)
        ->set('view', 'list')
        ->call('onlyMine')
        ->assertSet('assigneeIds', [$member->id])
        ->assertSeeText('Mía')
        ->assertDontSeeText('De nadie');
});

it('moves a task to the first status of its project in the dropped category', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create();
    $task = Task::factory()->for($project)->create();

    Livewire::actingAs($owner)->test(Index::class)
        ->call('moveTask', $task->id, 0, 'started');

    $task->refresh();

    expect($task->status->name)->toBe('En curso')
        ->and($task->activities()->where('event', TaskActivityEvent::StatusChanged)->count())->toBe(1);
});

it('does not move a task the user may not update or into a category the project lacks', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create();
    $member = User::factory()->memberOf($workspace)->create();
    $project->members()->attach($member);
    $task = Task::factory()->for($project)->create();
    $initial = $task->status_id;

    Livewire::actingAs($member)->test(Index::class)
        ->call('moveTask', $task->id, 0, 'started');

    expect($task->fresh()->status_id)->toBe($initial);

    $project->statuses()->where('category', 'started')->delete();

    Livewire::actingAs($owner)->test(Index::class)
        ->call('moveTask', $task->id, 0, 'started');

    expect($task->fresh()->status_id)->toBe($initial);
});

it('does not move a task of a project the user cannot open', function () {
    [, $workspace] = superAdminWithWorkspace();
    $member = User::factory()->memberOf($workspace)->create();
    $task = Task::factory()->for(Project::factory()->for($workspace)->create())->create();

    Livewire::actingAs($member)->test(Index::class)
        ->call('moveTask', $task->id, 0, 'started')
        ->assertNotFound();
});
