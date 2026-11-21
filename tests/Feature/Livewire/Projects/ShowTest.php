<?php

use App\Livewire\Projects\Show;
use App\Models\Project;
use App\Models\User;

it('redirects guests to the login screen', function () {
    $this->get(route('projects.show', Project::factory()->create()))->assertRedirect(route('login'));
});

it('shows the project page', function () {
    $project = Project::factory()->create(['name' => 'Portal de clientes', 'description' => 'Sitio para clientes']);

    $this->actingAs(User::factory()->create())
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertSeeLivewire(Show::class)
        ->assertSeeText('Portal de clientes')
        ->assertSeeText('Sitio para clientes')
        ->assertSee('<title>Portal de clientes · '.config('app.name').'</title>', escape: false);
});

it('returns 404 for a deleted project', function () {
    $project = Project::factory()->create();
    $project->delete();

    $this->actingAs(User::factory()->create())
        ->get(route('projects.show', $project))
        ->assertNotFound();
});
