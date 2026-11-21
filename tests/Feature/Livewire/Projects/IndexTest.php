<?php

use App\Enums\WorkspaceRole;
use App\Livewire\Projects\Index;
use App\Models\Account;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

it('redirects guests to the login screen', function () {
    $this->get(route('projects.index'))->assertRedirect(route('login'));
});

it('lists the projects of the current workspace', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    Project::factory()->for($workspace)->create(['name' => 'Portal de clientes']);
    Project::factory()->create(['name' => 'Proyecto ajeno']);

    $this->actingAs($owner)
        ->get(route('projects.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class)
        ->assertSeeText('Portal de clientes')
        ->assertDontSeeText('Proyecto ajeno');
});

it('sends a super admin without workspaces to create one', function () {
    $owner = User::factory()->create();
    Account::openFor($owner);

    $this->actingAs($owner)->get(route('projects.index'))->assertRedirect(route('workspaces.index'));
});

it('shows admins and members only their assigned projects (RN-21)', function () {
    [, $workspace] = superAdminWithWorkspace();
    $member = User::factory()->memberOf($workspace)->create();
    $assigned = Project::factory()->for($workspace)->create(['name' => 'Asignado']);
    Project::factory()->for($workspace)->create(['name' => 'Ajeno']);
    $assigned->members()->attach($member);

    Livewire::actingAs($member)
        ->test(Index::class)
        ->assertSeeText('Asignado')
        ->assertDontSeeText('Ajeno')
        ->assertDontSeeText('Nuevo proyecto');
});

it('filters projects by search term and client', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $client = Client::factory()->for($workspace)->create(['name' => 'Andes Café']);
    Project::factory()->for($workspace)->for($client)->create(['name' => 'Portal de clientes']);
    Project::factory()->for($workspace)->create(['name' => 'Migración de pagos']);

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->set('search', 'portal')
        ->assertSeeText('Portal de clientes')
        ->assertDontSeeText('Migración de pagos')
        ->set('search', '')
        ->set('client', $client->uuid)
        ->assertSeeText('Portal de clientes')
        ->assertDontSeeText('Migración de pagos');
});

it('sorts projects by name and ignores columns that are not allowed', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    Project::factory()->for($workspace)->create(['name' => 'Beta']);
    Project::factory()->for($workspace)->create(['name' => 'Alfa']);

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->set('sort', ['column' => 'name', 'direction' => 'asc'])
        ->assertSeeTextInOrder(['Alfa', 'Beta'])
        ->set('sort', ['column' => 'name', 'direction' => 'desc'])
        ->assertSeeTextInOrder(['Beta', 'Alfa'])
        ->set('sort', ['column' => 'password; drop table projects', 'direction' => 'asc'])
        ->assertOk();
});

it('paginates projects', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    Project::factory()->for($workspace)->count(12)->create();

    $component = Livewire::actingAs($owner)->test(Index::class);

    expect($component->instance()->rows->total())->toBe(12)
        ->and($component->instance()->rows->count())->toBe(10);
});

it('creates a project with the default workflow and its team (F-05)', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $client = Client::factory()->for($workspace)->create();
    $member = User::factory()->memberOf($workspace)->create();

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->call('create')
        ->set('form.name', 'Portal nuevo')
        ->set('form.icon', '🚀')
        ->set('form.client_id', $client->id)
        ->set('form.team_ids', [$member->id])
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('projects-changed');

    $project = $workspace->projects()->where('name', 'Portal nuevo')->sole();

    expect($project->client_id)->toBe($client->id)
        ->and($project->created_by_id)->toBe($owner->id)
        ->and($project->statuses()->pluck('name')->all())->toBe(['Backlog', 'Por hacer', 'En curso', 'Hecho', 'Cancelado'])
        ->and($project->initialStatus->name)->toBe('Backlog')
        ->and($project->priorities()->count())->toBe(5)
        ->and($project->taskLabels()->count())->toBe(0)
        ->and($project->members()->pluck('users.id')->all())->toBe([$member->id]);
});

it('validates the project form', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $foreignClient = Client::factory()->create();

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->call('create')
        ->set('form.name', '')
        ->set('form.client_id', $foreignClient->id)
        ->call('save')
        ->assertHasErrors(['form.name' => 'required', 'form.client_id']);
});

it('forbids admins from creating projects (A-15)', function () {
    [, $workspace] = superAdminWithWorkspace();
    $admin = User::factory()->memberOf($workspace, WorkspaceRole::Admin)->create();

    Livewire::actingAs($admin)->test(Index::class)->call('create')->assertForbidden();
});

it('sends a project to the trash', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create();

    Livewire::actingAs($owner)->test(Index::class)->call('delete', $project->uuid);

    expect($project->fresh()->trashed())->toBeTrue()
        ->and($project->fresh()->deleted_by_id)->toBe($owner->id);
});
