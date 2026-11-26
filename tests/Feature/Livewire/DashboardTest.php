<?php

use App\Enums\BoardEntryStatus;
use App\Enums\StatusCategory;
use App\Enums\WorkspaceRole;
use App\Livewire\Dashboard;
use App\Models\Account;
use App\Models\BoardEntry;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 6, 17)->setTime(10, 0));
});

/**
 * @param  array<string, mixed>  $attributes
 */
function openTask(Project $project, array $attributes = []): Task
{
    return Task::factory()->for($project)->create($attributes);
}

function priorityOf(Project $project, string $name): int
{
    return $project->priorities()->where('name', $name)->value('id');
}

it('redirects guests to the login screen', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('renders the dashboard inside the panel layout', function () {
    [$owner] = superAdminWithWorkspace(['name' => 'Ada Lovelace']);

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeLivewire(Dashboard::class)
        ->assertSeeText('Ada')
        ->assertSeeText('Atrasadas')
        ->assertSeeText('Requiere atención');
});

it('sorts open tasks into overdue, today and the next days', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create(['due_on' => null]);

    openTask($project, ['name' => 'Atrasada', 'ends_on' => today()->subDays(3)]);
    openTask($project, ['name' => 'Vence hoy', 'ends_on' => today()]);
    openTask($project, ['name' => 'Empieza hoy', 'starts_on' => today(), 'ends_on' => today()->addDays(2)]);
    openTask($project, ['name' => 'Pasado mañana', 'ends_on' => today()->addDays(2)]);
    openTask($project, ['name' => 'Fuera de la semana', 'ends_on' => today()->addDays(8)]);
    openTask($project, ['name' => 'Sin plazo']);

    $component = Livewire::actingAs($owner)->test(Dashboard::class)->instance();

    expect($component->overdueTasks->pluck('name')->all())->toBe(['Atrasada'])
        ->and($component->todayTasks->pluck('name')->sort()->values()->all())->toBe(['Empieza hoy', 'Vence hoy'])
        ->and($component->upcomingTasks->keys()->all())->toBe(['2026-06-19'])
        ->and($component->upcomingTasks->flatten()->pluck('name')->all())->toBe(['Pasado mañana'])
        ->and($component->undatedCount)->toBe(1);
});

it('puts the most urgent overdue tasks first', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create(['due_on' => null]);

    openTask($project, ['name' => 'Baja antigua', 'ends_on' => today()->subDays(9), 'priority_id' => priorityOf($project, 'baja')]);
    openTask($project, ['name' => 'Urgente', 'ends_on' => today()->subDay(), 'priority_id' => priorityOf($project, 'urgente')]);

    $component = Livewire::actingAs($owner)->test(Dashboard::class);

    expect($component->instance()->overdueTasks->pluck('name')->all())->toBe(['Urgente', 'Baja antigua']);
    $component->assertSeeText('1 de alta prioridad');
});

it('leaves out closed tasks, paused projects and projects the user cannot open', function () {
    [, $workspace] = superAdminWithWorkspace();
    $member = User::factory()->memberOf($workspace)->create();
    $mine = Project::factory()->for($workspace)->create(['due_on' => null]);
    $mine->members()->attach($member);
    $paused = Project::factory()->for($workspace)->paused()->create(['due_on' => null]);
    $paused->members()->attach($member);
    $foreign = Project::factory()->for($workspace)->create(['due_on' => null]);

    openTask($mine, ['name' => 'Abierta', 'ends_on' => today()]);
    openTask($mine, ['name' => 'Cerrada', 'ends_on' => today(), 'status_id' => $mine->statuses()->where('category', StatusCategory::Completed)->value('id')]);
    openTask($paused, ['name' => 'De proyecto en pausa', 'ends_on' => today()]);
    openTask($foreign, ['name' => 'Ajena', 'ends_on' => today()]);

    Livewire::actingAs($member)
        ->test(Dashboard::class)
        ->assertSeeText('Abierta')
        ->assertDontSeeText('Cerrada')
        ->assertDontSeeText('De proyecto en pausa')
        ->assertDontSeeText('Ajena');
});

it('shows only the tasks assigned to the user with "Solo mías"', function () {
    [, $workspace] = superAdminWithWorkspace();
    $member = User::factory()->memberOf($workspace)->create();
    $project = Project::factory()->for($workspace)->create(['due_on' => null]);
    $project->members()->attach($member);

    openTask($project, ['name' => 'Mía', 'ends_on' => today()])->assignees()->attach($member, ['project_id' => $project->id]);
    openTask($project, ['name' => 'Del equipo', 'ends_on' => today()]);

    Livewire::actingAs($member)
        ->test(Dashboard::class)
        ->assertSeeText('Del equipo')
        ->set('onlyMine', true)
        ->assertSeeText('Mía')
        ->assertDontSeeText('Del equipo');
});

it('warns about work that needs attention', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create(['name' => 'Portal', 'due_on' => today()->addDays(3)]);

    openTask($project, ['name' => 'Urgente sin fecha', 'priority_id' => priorityOf($project, 'alta')]);
    openTask($project, ['name' => 'Nadie la tiene', 'ends_on' => today()->addDay()]);
    openTask($project, [
        'name' => 'Estancada',
        'status_id' => $project->statuses()->where('category', StatusCategory::Started)->value('id'),
        'updated_at' => now()->subDays(20),
    ]);

    $warnings = collect(Livewire::actingAs($owner)->test(Dashboard::class)->instance()->attention)->keyBy('key');

    expect($warnings->map(fn (array $warning): int => $warning['count'])->all())->toBe([
        'high-undated' => 1,
        'unassigned' => 1,
        'stalled' => 1,
        'project-deadlines' => 1,
    ])
        ->and($warnings['high-undated']['examples'][0]['label'])->toBe('Urgente sin fecha')
        ->and($warnings['stalled']['examples'][0]['label'])->toBe('Estancada')
        ->and($warnings['project-deadlines']['examples'][0]['label'])->toContain('Portal');
});

it('lists the planned board notes of today', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create(['due_on' => null]);

    BoardEntry::factory()->for($project)->create(['occurs_on' => today(), 'body' => 'Llamar al cliente', 'status' => BoardEntryStatus::Planned]);
    BoardEntry::factory()->for($project)->create(['occurs_on' => today()->addDay(), 'body' => 'Nota de mañana', 'status' => BoardEntryStatus::Planned]);

    Livewire::actingAs($owner)
        ->test(Dashboard::class)
        ->assertSeeText('Llamar al cliente')
        ->assertDontSeeText('Nota de mañana');
});

it('completes a task from the home page', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create(['due_on' => null]);
    $task = openTask($project, ['name' => 'Enviar informe', 'ends_on' => today()]);

    Livewire::actingAs($owner)
        ->test(Dashboard::class)
        ->call('complete', $task->id)
        ->assertDontSeeText('Enviar informe');

    expect($task->fresh()->completed_at)->not->toBeNull()
        ->and($task->fresh()->status->category)->toBe(StatusCategory::Completed);
});

it('does not let a member complete a task assigned to someone else', function () {
    [, $workspace] = superAdminWithWorkspace();
    $member = User::factory()->memberOf($workspace)->create();
    $project = Project::factory()->for($workspace)->create(['due_on' => null]);
    $project->members()->attach($member);
    $task = openTask($project, ['ends_on' => today()]);

    Livewire::actingAs($member)
        ->test(Dashboard::class)
        ->call('complete', $task->id);

    expect($task->fresh()->completed_at)->toBeNull();
});

it('shows nothing to a client user (E10)', function () {
    [, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->visibleToClient()->create(['due_on' => null]);
    openTask($project, ['name' => 'Tarea interna', 'ends_on' => today()]);
    $clientUser = User::factory()->memberOf($workspace, WorkspaceRole::Client)->create();

    Livewire::actingAs($clientUser)
        ->test(Dashboard::class)
        ->assertSeeText('Por ahora no hay contenido para ti')
        ->assertDontSeeText('Tarea interna');
});

it('invites a super admin without workspaces to create one', function () {
    $owner = User::factory()->create();
    Account::openFor($owner);

    Livewire::actingAs($owner)
        ->test(Dashboard::class)
        ->assertSeeText('Crea tu primer espacio de trabajo');
});
