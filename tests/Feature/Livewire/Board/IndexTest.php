<?php

use App\Enums\BoardEntryStatus;
use App\Enums\Permission;
use App\Enums\ProjectPriorityLevel;
use App\Enums\WorkspaceRole;
use App\Livewire\Board\Index;
use App\Models\BoardEntry;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

it('redirects guests to the login screen', function () {
    $this->get(route('board.index'))->assertRedirect(route('login'));
});

it('forbids a client user from opening the board', function () {
    [, $workspace] = superAdminWithWorkspace();
    $clientUser = User::factory()->memberOf($workspace, WorkspaceRole::Client)->create();

    $this->actingAs($clientUser)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSeeText('Pizarra');

    $this->actingAs($clientUser)
        ->get(route('board.index'))
        ->assertForbidden();
});

it('shows each person only the projects they can open', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    Project::factory()->for($workspace)->create(['name' => 'Portal propio']);
    $member = User::factory()->memberOf($workspace)->create();
    $assigned = Project::factory()->for($workspace)->create(['name' => 'Asignado']);
    Project::factory()->for($workspace)->create(['name' => 'Ajeno']);
    $assigned->members()->attach($member);

    $this->actingAs($owner)
        ->get(route('board.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class)
        ->assertSeeText('Portal propio')
        ->assertSeeText('Asignado')
        ->assertSeeText('Planificada')
        ->assertSeeText('Sin trabajo');

    Livewire::actingAs($member)
        ->test(Index::class)
        ->assertSeeText('Asignado')
        ->assertDontSeeText('Ajeno')
        ->assertDontSeeText('Portal propio');
});

it('shares note editing with the project team and keeps pause for project editors', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create();
    $member = User::factory()->memberOf($workspace)->create();
    $project->members()->attach($member);
    $admin = User::factory()->memberOf($workspace, WorkspaceRole::Admin)->create();
    $other = Project::factory()->for($workspace)->create();
    $other->members()->attach($admin);
    $client = User::factory()->memberOf($workspace, WorkspaceRole::Client)->create();
    $entry = BoardEntry::factory()->for($project)->create(['created_by_id' => $owner->id]);

    expect($member->hasWorkspacePermission($workspace, Permission::BoardUpdate))->toBeTrue()
        ->and($member->can('view', $entry))->toBeTrue()
        ->and($member->can('update', $entry))->toBeTrue()
        ->and($member->can('delete', $entry))->toBeTrue()
        ->and($member->can('update', $project))->toBeFalse()
        ->and($admin->can('update', $other))->toBeTrue()
        ->and($admin->can('create', [BoardEntry::class, $project]))->toBeFalse()
        ->and($client->can('viewAny', [BoardEntry::class, $workspace]))->toBeFalse()
        ->and($owner->can('create', [BoardEntry::class, $project]))->toBeTrue();
});

it('rejects a note on a project or task the user cannot use', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create();
    $member = User::factory()->memberOf($workspace)->create();
    $project->members()->attach($member);
    $foreign = Project::factory()->for($workspace)->create();
    $foreignTask = Task::factory()->for($foreign)->create();
    $otherWorkspace = Project::factory()->create();
    $outside = BoardEntry::factory()->for($otherWorkspace)->create();

    Livewire::actingAs($member)
        ->test(Index::class)
        ->call('addEntry', $foreign->uuid, '2026-11-24', 'API', false, '')
        ->assertNotFound();

    Livewire::actingAs($member)
        ->test(Index::class)
        ->call('addEntry', $project->uuid, '2026-11-24', 'API', false, $foreignTask->uuid)
        ->assertNotFound();

    Livewire::actingAs($member)
        ->test(Index::class)
        ->call('toggleDone', $outside->uuid)
        ->assertNotFound();

    Livewire::actingAs($member)
        ->test(Index::class)
        ->call('togglePause', $project->uuid)
        ->assertForbidden();

    Livewire::actingAs($member)
        ->test(Index::class)
        ->call('setPriority', $project->uuid, ProjectPriorityLevel::Urgent->value)
        ->assertForbidden();

    expect(BoardEntry::query()->where('body', 'API')->exists())->toBeFalse();
});

it('creates a planned note, a done note, and marks one done', function () {
    $this->travelTo(Carbon::parse('2026-11-24 12:00:00'));
    [$owner, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create(['name' => 'API pública']);
    $task = Task::factory()->for($project)->create(['name' => 'Contrato']);
    $member = User::factory()->memberOf($workspace)->create();
    $project->members()->attach($member);

    Livewire::actingAs($member)
        ->test(Index::class)
        ->call('addEntry', $project->uuid, '2026-11-24', '  API  ', false, $task->uuid)
        ->assertReturned(true)
        ->call('addEntry', $project->uuid, '2026-11-24', '   ', false, '')
        ->assertReturned(false)
        ->call('addEntry', $project->uuid, '2026-11-24', str_repeat('a', 81), false, '')
        ->assertReturned(false)
        ->call('addEntry', $project->uuid, '2026-11-23', 'Backup', true, '')
        ->assertReturned(true);

    $planned = BoardEntry::query()->where('body', 'API')->first();
    $done = BoardEntry::query()->where('body', 'Backup')->first();

    expect($planned)->not->toBeNull()
        ->and($planned->created_by_id)->toBe($member->id)
        ->and($planned->status)->toBe(BoardEntryStatus::Planned)
        ->and($planned->task_id)->toBe($task->id)
        ->and($planned->workspace_id)->toBe($workspace->id)
        ->and($done->status)->toBe(BoardEntryStatus::Done)
        ->and($done->completed_by_id)->toBe($member->id)
        ->and($done->isOverdue())->toBeFalse()
        ->and(BoardEntry::query()->count())->toBe(2);

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->call('toggleDone', $planned->uuid)
        ->assertSee('data-entry="API" data-overdue="0"', false);

    expect($planned->fresh()->status)->toBe(BoardEntryStatus::Done)
        ->and($planned->fresh()->completed_by_id)->toBe($owner->id);

    Livewire::actingAs($member)
        ->test(Index::class)
        ->call('updateEntry', $done->uuid, 'Respaldo', '')
        ->call('deleteEntry', $done->uuid);

    expect(BoardEntry::query()->where('body', 'Respaldo')->exists())->toBeFalse()
        ->and(BoardEntry::query()->where('body', 'Backup')->exists())->toBeFalse();
});

it('marks a project overdue only when its due date passed and it is not paused', function () {
    $this->travelTo(Carbon::parse('2026-11-24 12:00:00'));
    [$owner, $workspace] = superAdminWithWorkspace();

    $overdue = Project::factory()->for($workspace)->create([
        'name' => 'Vence ayer',
        'due_on' => '2026-11-23',
        'priority' => ProjectPriorityLevel::Urgent,
    ]);
    $paused = Project::factory()->for($workspace)->paused()->create([
        'name' => 'Pausado vencido',
        'due_on' => '2026-11-20',
    ]);
    $current = Project::factory()->for($workspace)->create([
        'name' => 'Vence hoy',
        'due_on' => '2026-11-24',
    ]);

    BoardEntry::factory()->for($overdue)->create([
        'body' => 'Nota vieja',
        'occurs_on' => '2026-11-23',
        'created_by_id' => $owner->id,
    ]);
    BoardEntry::factory()->for($overdue)->done()->create([
        'body' => 'Nota cerrada',
        'occurs_on' => '2026-11-23',
        'created_by_id' => $owner->id,
    ]);

    $open = Task::factory()->for($overdue)->create([
        'name' => 'Tarea vencida',
        'ends_on' => '2026-11-22',
        'created_by_id' => $owner->id,
    ]);
    Task::factory()->for($overdue)->create([
        'name' => 'Tarea a tiempo',
        'ends_on' => '2026-11-24',
        'created_by_id' => $owner->id,
    ]);

    expect($overdue->isOverdue())->toBeTrue()
        ->and($paused->fresh()->isOverdue())->toBeFalse()
        ->and($current->isOverdue())->toBeFalse()
        ->and($open->fresh()->completed_at)->toBeNull();

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->assertSee('data-project="Vence ayer" data-project-overdue="1"', false)
        ->assertSee('data-open-overdue="1"', false)
        ->assertSee('data-project="Pausado vencido" data-project-overdue="0"', false)
        ->assertSee('data-paused="1"', false)
        ->assertSee('data-entry="Nota vieja" data-overdue="1"', false)
        ->assertSee('data-entry="Nota cerrada" data-overdue="0"', false)
        ->assertSee('data-project="Vence hoy" data-project-overdue="0"', false)
        ->set('onlyOverdue', true)
        ->assertSee('Vence ayer')
        ->assertDontSee('Vence hoy')
        ->set('onlyOverdue', false)
        ->set('hidePaused', true)
        ->assertDontSee('Pausado vencido')
        ->assertSee('Vence ayer');
});

it('treats a day as worked only when it has a done note', function () {
    $this->travelTo(Carbon::parse('2026-11-24 12:00:00'));
    [$owner, $workspace] = superAdminWithWorkspace();
    $worked = Project::factory()->for($workspace)->create(['name' => 'Con trabajo', 'due_on' => null]);
    $idle = Project::factory()->for($workspace)->create(['name' => 'Sin trabajo hoy', 'due_on' => null]);
    $paused = Project::factory()->for($workspace)->paused()->create(['name' => 'En pausa hoy', 'due_on' => null]);

    BoardEntry::factory()->for($worked)->done()->create([
        'body' => 'Cerrado',
        'occurs_on' => '2026-11-24',
        'created_by_id' => $owner->id,
    ]);
    BoardEntry::factory()->for($idle)->create([
        'body' => 'Pendiente',
        'occurs_on' => '2026-11-24',
        'created_by_id' => $owner->id,
    ]);

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->assertSee('data-project="Con trabajo"', false)
        ->assertSee('data-date="2026-11-24" data-idle="0"', false)
        ->assertSee('data-date="2026-11-24" data-idle="1"', false)
        ->set('idleToday', true)
        ->assertSee('Sin trabajo hoy')
        ->assertDontSee('Con trabajo')
        ->assertDontSee('En pausa hoy')
        ->set('idleToday', false)
        ->set('search', 'pausa')
        ->assertSee('En pausa hoy')
        ->assertDontSee('Con trabajo');
});

it('keeps the shared row order and a personal hide', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $first = Project::factory()->for($workspace)->create(['name' => 'Alfa']);
    $second = Project::factory()->for($workspace)->create(['name' => 'Beta']);
    $third = Project::factory()->for($workspace)->create(['name' => 'Gamma']);
    $member = User::factory()->memberOf($workspace)->create();
    $first->members()->attach($member);

    Livewire::actingAs($member)
        ->test(Index::class)
        ->call('reorderProjects', $first->id, 0)
        ->assertForbidden();

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->call('reorderProjects', $third->id, 0)
        ->assertSeeTextInOrder(['Gamma', 'Alfa', 'Beta']);

    expect($third->fresh()->board_position)->toBeLessThan($first->fresh()->board_position)
        ->and($first->fresh()->board_position)->toBeLessThan($second->fresh()->board_position);

    Livewire::actingAs($member)
        ->test(Index::class)
        ->call('hideProject', $first->uuid)
        ->assertDontSee('Alfa');

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->assertSee('Alfa');

    Livewire::actingAs($member)
        ->test(Index::class)
        ->call('showProject', $first->uuid)
        ->assertSee('Alfa');
});

it('puts the week and the weekend in the address and snaps to Monday', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    Project::factory()->for($workspace)->create(['name' => 'Semana visible']);

    $this->actingAs($owner)
        ->get(route('board.index', ['semana' => '2026-11-18', 'fin_de_semana' => 1]))
        ->assertOk()
        ->assertSeeText('Sáb')
        ->assertSeeText('Dom');

    Livewire::actingAs($owner)
        ->withQueryParams(['semana' => '2026-11-18', 'fin_de_semana' => 1])
        ->test(Index::class)
        ->assertSet('week', '2026-11-16')
        ->assertSet('weekend', true)
        ->call('currentWeek');
});

it('uses America/Santiago for today when UTC is already the next day', function () {
    $previous = date_default_timezone_get();
    config(['app.timezone' => 'America/Santiago']);
    date_default_timezone_set('America/Santiago');

    try {
        $this->travelTo(Carbon::parse('2026-11-23 02:30:00', 'UTC'));

        [$owner, $workspace] = superAdminWithWorkspace();
        $project = Project::factory()->for($workspace)->create([
            'name' => 'Reloj',
            'due_on' => '2026-11-21',
        ]);

        BoardEntry::factory()->for($project)->create([
            'body' => 'Ayer Santiago',
            'occurs_on' => '2026-11-21',
            'created_by_id' => $owner->id,
        ]);
        BoardEntry::factory()->for($project)->create([
            'body' => 'Hoy Santiago',
            'occurs_on' => '2026-11-22',
            'created_by_id' => $owner->id,
        ]);

        expect(today()->toDateString())->toBe('2026-11-22')
            ->and($project->isOverdue())->toBeTrue();

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->assertSet('week', '2026-11-16')
            ->set('weekend', true)
            ->assertSee('data-entry="Ayer Santiago" data-overdue="1"', false)
            ->assertSee('data-entry="Hoy Santiago" data-overdue="0"', false);
    } finally {
        date_default_timezone_set($previous);
    }
});
