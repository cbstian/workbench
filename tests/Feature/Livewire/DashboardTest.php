<?php

use App\Livewire\Dashboard;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

it('redirects guests to the login screen', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('renders the dashboard inside the panel layout', function () {
    $this->actingAs(User::factory()->create(['name' => 'Ada Lovelace']))
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeLivewire(Dashboard::class)
        ->assertSeeText('Ada')
        ->assertSeeText('Proyectos')
        ->assertSee(route('projects.index'), escape: false);
});

it('computes the dashboard figures', function () {
    Project::factory()->count(2)->create(['is_visible_to_client' => false]);
    Project::factory()->visibleToClient()->create(['name' => 'Portal de prueba']);

    $component = Livewire::actingAs(User::factory()->create())->test(Dashboard::class);

    $component->assertSeeText('Portal de prueba');

    expect(collect($component->instance()->stats)->pluck('value', 'label')->all())
        ->toMatchArray([
            'Proyectos' => 3,
            'Visibles para el cliente' => 1,
        ]);
});
