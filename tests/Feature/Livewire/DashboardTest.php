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

it('builds the chart series from the projects', function () {
    $this->travelTo(now()->setDate(2026, 6, 15));

    Project::factory()->create(['created_at' => now()->subMonths(2), 'is_visible_to_client' => false]);
    Project::factory()->count(2)->create(['created_at' => now(), 'is_visible_to_client' => false]);
    Project::factory()->create(['created_at' => now()->subYear(), 'is_visible_to_client' => false]);
    Project::factory()->visibleToClient()->create(['created_at' => now()->subYear()]);

    $component = Livewire::actingAs(User::factory()->create())->test(Dashboard::class)->instance();

    expect($component->projectsPerMonthChart['series'][0]['data'])->toBe([0, 0, 0, 1, 0, 2])
        ->and($component->visibilityChart['series'])->toBe([1, 4]);
});
