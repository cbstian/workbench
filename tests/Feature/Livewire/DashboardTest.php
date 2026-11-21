<?php

use App\Enums\WorkspaceRole;
use App\Livewire\Dashboard;
use App\Models\Account;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

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
        ->assertSeeText('Proyectos')
        ->assertSee(route('projects.index'), escape: false);
});

it('computes the dashboard figures of the current workspace', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    Project::factory()->for($workspace)->count(2)->create(['is_visible_to_client' => false]);
    Project::factory()->for($workspace)->visibleToClient()->create(['name' => 'Portal de prueba']);
    Project::factory()->create(['name' => 'De otra cuenta']);

    $component = Livewire::actingAs($owner)->test(Dashboard::class);

    $component->assertSeeText('Portal de prueba')->assertDontSeeText('De otra cuenta');

    expect(collect($component->instance()->stats)->pluck('value', 'label')->all())
        ->toMatchArray([
            'Proyectos' => 3,
            'Visibles para el cliente' => 1,
        ]);
});

it('builds the chart series from the projects', function () {
    $this->travelTo(now()->setDate(2026, 6, 15));
    [$owner, $workspace] = superAdminWithWorkspace();

    Project::factory()->for($workspace)->create(['created_at' => now()->subMonths(2), 'is_visible_to_client' => false]);
    Project::factory()->for($workspace)->count(2)->create(['created_at' => now(), 'is_visible_to_client' => false]);
    Project::factory()->for($workspace)->create(['created_at' => now()->subYear(), 'is_visible_to_client' => false]);
    Project::factory()->for($workspace)->visibleToClient()->create(['created_at' => now()->subYear()]);

    $component = Livewire::actingAs($owner)->test(Dashboard::class)->instance();

    expect($component->projectsPerMonthChart['series'][0]['data'])->toBe([0, 0, 0, 1, 0, 2])
        ->and($component->visibilityChart['series'])->toBe([1, 4]);
});

it('shows nothing to a client user (E10)', function () {
    [, $workspace] = superAdminWithWorkspace();
    Project::factory()->for($workspace)->visibleToClient()->create(['name' => 'Portal visible']);
    $clientUser = User::factory()->memberOf($workspace, WorkspaceRole::Client)->create();

    Livewire::actingAs($clientUser)
        ->test(Dashboard::class)
        ->assertSeeText('Por ahora no hay contenido para ti')
        ->assertDontSeeText('Portal visible');
});

it('invites a super admin without workspaces to create one', function () {
    $owner = User::factory()->create();
    Account::openFor($owner);

    Livewire::actingAs($owner)
        ->test(Dashboard::class)
        ->assertSeeText('Crea tu primer espacio de trabajo');
});
