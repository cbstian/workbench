<?php

use App\Livewire\Navigation\ProjectLinks;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

it('lists projects alphabetically in the sidebar', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $beta = Project::factory()->for($workspace)->create(['name' => 'Beta']);
    Project::factory()->for($workspace)->create(['name' => 'Alfa']);

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeLivewire(ProjectLinks::class)
        ->assertSeeTextInOrder(['Alfa', 'Beta'])
        ->assertSee(route('projects.show', $beta), escape: false);
});

it('marks the open project as active', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create(['name' => 'Portal']);

    $this->actingAs($owner)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertSee('aria-current="page"', escape: false);
});

it('caps the list and links to the full index', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    Project::factory()->for($workspace)->count(ProjectLinks::LIMIT + 1)->create();

    Livewire::actingAs($owner)
        ->test(ProjectLinks::class)
        ->assertSeeText('Ver todos');
});

it('refreshes when projects change', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $component = Livewire::actingAs($owner)->test(ProjectLinks::class);

    Project::factory()->for($workspace)->create(['name' => 'Recién creado']);

    $component->dispatch('projects-changed')->assertSeeText('Recién creado');
});

it('lists only the projects a member is assigned to', function () {
    [, $workspace] = superAdminWithWorkspace();
    $member = User::factory()->memberOf($workspace)->create();
    $assigned = Project::factory()->for($workspace)->create(['name' => 'Asignado']);
    Project::factory()->for($workspace)->create(['name' => 'Ajeno']);
    $assigned->members()->attach($member);

    Livewire::actingAs($member)
        ->test(ProjectLinks::class)
        ->assertSeeText('Asignado')
        ->assertDontSeeText('Ajeno');
});
