<?php

use App\Livewire\Navigation\ProjectLinks;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

it('lists projects alphabetically in the sidebar', function () {
    $beta = Project::factory()->create(['name' => 'Beta']);
    Project::factory()->create(['name' => 'Alfa']);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeLivewire(ProjectLinks::class)
        ->assertSeeTextInOrder(['Alfa', 'Beta'])
        ->assertSee(route('projects.show', $beta), escape: false);
});

it('marks the open project as active', function () {
    $project = Project::factory()->create(['name' => 'Portal']);

    $this->actingAs(User::factory()->create())
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertSee('aria-current="page"', escape: false);
});

it('caps the list and links to the full index', function () {
    Project::factory()->count(ProjectLinks::LIMIT + 1)->create();

    Livewire::actingAs(User::factory()->create())
        ->test(ProjectLinks::class)
        ->assertSeeText('Ver todos');
});

it('refreshes when projects change', function () {
    $component = Livewire::actingAs(User::factory()->create())->test(ProjectLinks::class);

    Project::factory()->create(['name' => 'Recién creado']);

    $component->dispatch('projects-changed')->assertSeeText('Recién creado');
});
