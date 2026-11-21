<?php

use App\Enums\WorkspaceRole;
use App\Livewire\Trash\Index;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

it('lists what was deleted in the current workspace (F-10)', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $this->actingAs($owner);
    Client::factory()->for($workspace)->create(['name' => 'Cliente borrado'])->delete();
    Client::factory()->for($workspace)->create(['name' => 'Cliente activo']);
    Client::factory()->create(['name' => 'Borrado ajeno'])->delete();

    $this->get(route('trash.index'))
        ->assertOk()
        ->assertSeeText('Cliente borrado')
        ->assertSeeText($owner->name)
        ->assertDontSeeText('Cliente activo')
        ->assertDontSeeText('Borrado ajeno')
        ->assertDontSeeText('Vaciar');
});

it('restores an element', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create();
    $project->delete();

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->call('restore', 'project', $project->id)
        ->assertDispatched('projects-changed');

    expect($project->fresh()->trashed())->toBeFalse()
        ->and($project->fresh()->deleted_by_id)->toBeNull();
});

it('renames a restored element whose name was taken meanwhile', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $deleted = Client::factory()->for($workspace)->create(['name' => 'Casa Mora']);
    $deleted->delete();
    Client::factory()->for($workspace)->create(['name' => 'Casa Mora']);

    Livewire::actingAs($owner)->test(Index::class)->call('restore', 'client', $deleted->id);

    expect($deleted->fresh())->trashed()->toBeFalse()->name->toBe('Casa Mora (restaurado)');
});

it('cannot restore elements of another workspace', function () {
    [$owner] = superAdminWithWorkspace();
    $foreign = Client::factory()->create();
    $foreign->delete();

    Livewire::actingAs($owner)->test(Index::class)->call('restore', 'client', $foreign->id)->assertNotFound();
});

it('forbids members and client users', function (WorkspaceRole $role) {
    [, $workspace] = superAdminWithWorkspace();
    $user = User::factory()->memberOf($workspace, $role)->create();

    $this->actingAs($user)->get(route('trash.index'))->assertForbidden();
})->with([WorkspaceRole::Member, WorkspaceRole::Client]);

it('shows an admin only what was deleted in their projects', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $admin = User::factory()->memberOf($workspace, WorkspaceRole::Admin)->create();
    $own = Project::factory()->for($workspace)->create();
    $foreign = Project::factory()->for($workspace)->create();
    $own->members()->attach($admin);
    $this->actingAs($owner);
    $own->taskLabels()->create(['name' => 'Etiqueta propia'])->delete();
    $foreignLabel = $foreign->taskLabels()->create(['name' => 'Etiqueta ajena']);
    $foreignLabel->delete();
    Client::factory()->for($workspace)->create(['name' => 'Cliente borrado'])->delete();
    Project::factory()->for($workspace)->create(['name' => 'Proyecto borrado'])->delete();

    $this->actingAs($admin)
        ->get(route('trash.index'))
        ->assertOk()
        ->assertSeeText('Etiqueta propia')
        ->assertDontSeeText('Etiqueta ajena')
        ->assertDontSeeText('Cliente borrado')
        ->assertDontSeeText('Proyecto borrado');

    Livewire::actingAs($admin)->test(Index::class)->call('restore', 'task_label', $foreignLabel->id)->assertNotFound();
});

it('lets an admin restore an element of their project', function () {
    [, $workspace] = superAdminWithWorkspace();
    $admin = User::factory()->memberOf($workspace, WorkspaceRole::Admin)->create();
    $project = Project::factory()->for($workspace)->create();
    $project->members()->attach($admin);
    $status = $project->statuses()->where('name', 'En curso')->sole();
    $status->delete();

    Livewire::actingAs($admin)->test(Index::class)->call('restore', 'project_status', $status->id);

    expect($status->fresh()->trashed())->toBeFalse();
});
