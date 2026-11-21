<?php

use App\Livewire\Projects\Show;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;

it('redirects guests to the login screen', function () {
    $this->get(route('projects.show', Project::factory()->create()))->assertRedirect(route('login'));
});

it('shows the project page', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create(['name' => 'Portal de clientes', 'description' => 'Sitio para clientes']);

    $this->actingAs($owner)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertSeeLivewire(Show::class)
        ->assertSeeText('Portal de clientes')
        ->assertSeeText('Sitio para clientes')
        ->assertSeeText('Backlog')
        ->assertSee(route('projects.settings', $project), escape: false)
        ->assertSee('<title>Portal de clientes · '.config('app.name').'</title>', escape: false);
});

it('switches to the workspace of the opened project', function () {
    [$owner, $first] = superAdminWithWorkspace();
    $second = Workspace::factory()->create(['account_id' => $first->account_id, 'name' => 'Segundo']);
    $project = Project::factory()->for($second)->create();

    $this->actingAs($owner)
        ->withSession([CurrentWorkspace::SESSION_KEY => $first->id])
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertSessionHas(CurrentWorkspace::SESSION_KEY, $second->id);
});

it('forbids projects of other accounts or not assigned (A-02, A-16)', function () {
    [$owner] = superAdminWithWorkspace();
    [, $workspace] = superAdminWithWorkspace();
    $member = User::factory()->memberOf($workspace)->create();
    $project = Project::factory()->for($workspace)->create();

    $this->actingAs($owner)->get(route('projects.show', $project))->assertForbidden();
    $this->actingAs($member)->get(route('projects.show', $project))->assertForbidden();
});

it('returns 404 for a deleted project', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create();
    $project->delete();

    $this->actingAs($owner)
        ->get(route('projects.show', $project))
        ->assertNotFound();
});
