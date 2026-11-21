<?php

use App\Enums\WorkspaceRole;
use App\Models\User;

it('shows the permissions of each role, read from spatie', function () {
    [$owner] = superAdminWithWorkspace();

    $this->actingAs($owner)
        ->get(route('roles.index'))
        ->assertOk()
        ->assertSeeText('Roles y permisos')
        ->assertSeeText('Configurar estados, prioridades y etiquetas de sus proyectos')
        ->assertSeeText('Exclusivo del Super admin');
});

it('forbids users who are not super admins', function () {
    [, $workspace] = superAdminWithWorkspace();
    $admin = User::factory()->memberOf($workspace, WorkspaceRole::Admin)->create();

    $this->actingAs($admin)->get(route('roles.index'))->assertForbidden();
});
