<?php

use App\Enums\WorkspaceRole;
use App\Models\User;

it('renders the sidebar links registered in the navigation config', function () {
    [$owner] = superAdminWithWorkspace();

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('projects.index'), escape: false)
        ->assertSee(route('settings.profile'), escape: false)
        ->assertSeeText('Espacio de trabajo');
});

it('hides items whose ability the user lacks', function () {
    config()->set('navigation.groups.2.items.0.can', 'manage-everything');

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSeeText('Espacio de trabajo');
});

it('skips items whose route does not exist', function () {
    config()->set('navigation.groups.2.items', [
        ['label' => 'Módulo pendiente', 'route' => 'missing.index', 'icon' => 'cube'],
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSeeText('Módulo pendiente');
});

it('renders labelled groups and items with children as collapsible', function () {
    [$owner] = superAdminWithWorkspace();

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('wb-nav-group-espacio-de-trabajo', escape: false)
        ->assertSee('Expandir Proyectos', escape: false);
});

it('shows the administration group only to the super admin', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $member = User::factory()->memberOf($workspace)->create();

    $this->actingAs($owner)->get(route('dashboard'))
        ->assertSeeText('Administración')
        ->assertSee(route('users.index'), escape: false)
        ->assertSee(route('roles.index'), escape: false);

    $this->actingAs($member)->get(route('dashboard'))
        ->assertDontSeeText('Administración')
        ->assertDontSee(route('users.index'), escape: false);
});

it('shows an admin the users and trash of their projects, nothing else', function () {
    [, $workspace] = superAdminWithWorkspace();
    $admin = User::factory()->memberOf($workspace, WorkspaceRole::Admin)->create();

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertSee(route('users.index'), escape: false)
        ->assertSee(route('trash.index'), escape: false)
        ->assertDontSee(route('clients.index'), escape: false)
        ->assertDontSee(route('workspaces.index'), escape: false)
        ->assertDontSee(route('roles.index'), escape: false);
});
