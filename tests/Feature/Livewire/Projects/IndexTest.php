<?php

use App\Livewire\Projects\Index;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

it('redirects guests to the login screen', function () {
    $this->get(route('projects.index'))->assertRedirect(route('login'));
});

it('lists projects in the panel', function () {
    Project::factory()->create(['name' => 'Portal de clientes']);

    $this->actingAs(User::factory()->create())
        ->get(route('projects.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class)
        ->assertSeeText('Portal de clientes');
});

it('filters projects by search term', function () {
    Project::factory()->create(['name' => 'Portal de clientes']);
    Project::factory()->create(['name' => 'Migración de pagos']);

    Livewire::actingAs(User::factory()->create())
        ->test(Index::class)
        ->set('search', 'portal')
        ->assertSeeText('Portal de clientes')
        ->assertDontSeeText('Migración de pagos');
});

it('sorts projects by name', function () {
    Project::factory()->create(['name' => 'Beta']);
    Project::factory()->create(['name' => 'Alfa']);

    Livewire::actingAs(User::factory()->create())
        ->test(Index::class)
        ->set('sort', ['column' => 'name', 'direction' => 'asc'])
        ->assertSeeTextInOrder(['Alfa', 'Beta'])
        ->set('sort', ['column' => 'name', 'direction' => 'desc'])
        ->assertSeeTextInOrder(['Beta', 'Alfa']);
});

it('ignores sorting by a column that is not allowed', function () {
    Project::factory()->create(['name' => 'Alfa']);

    Livewire::actingAs(User::factory()->create())
        ->test(Index::class)
        ->set('sort', ['column' => 'password; drop table projects', 'direction' => 'asc'])
        ->assertOk()
        ->assertSeeText('Alfa');
});

it('paginates projects', function () {
    Project::factory()->count(12)->create();

    $component = Livewire::actingAs(User::factory()->create())->test(Index::class);

    expect($component->instance()->rows->total())->toBe(12)
        ->and($component->instance()->rows->count())->toBe(10);
});
