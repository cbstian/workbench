<?php

use App\Enums\WorkspaceRole;
use App\Livewire\Navigation\WorkspaceSwitcher;
use App\Livewire\Workspaces\Index;
use App\Models\Account;
use App\Models\User;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Livewire\Livewire;

it('redirects guests to the login screen', function () {
    $this->get(route('workspaces.index'))->assertRedirect(route('login'));
});

it('lists the workspaces of the account only', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    Workspace::factory()->create(['name' => 'De otra cuenta']);

    $this->actingAs($owner)
        ->get(route('workspaces.index'))
        ->assertOk()
        ->assertSeeText($workspace->name)
        ->assertDontSeeText('De otra cuenta');
});

it('creates several workspaces and works in the first one (F-02)', function () {
    $owner = User::factory()->create();
    Account::openFor($owner);

    $component = Livewire::actingAs($owner)->test(Index::class);

    $component->call('create')->set('form.name', 'Estudio')->call('save')->assertHasNoErrors()->assertDispatched('workspaces-changed');
    $component->call('create')->set('form.name', 'Personal')->call('save');

    expect($owner->ownedWorkspaces()->pluck('workspaces.name')->sort()->values()->all())->toBe(['Estudio', 'Personal'])
        ->and(session(CurrentWorkspace::SESSION_KEY))->toBe($owner->ownedWorkspaces()->where('workspaces.name', 'Estudio')->value('workspaces.id'));
});

it('requires a name', function () {
    [$owner] = superAdminWithWorkspace();

    Livewire::actingAs($owner)->test(Index::class)
        ->call('create')->set('form.name', '')->call('save')
        ->assertHasErrors(['form.name' => 'required']);
});

it('forbids admins, members and client users (A-20)', function (WorkspaceRole $role) {
    [, $workspace] = superAdminWithWorkspace();
    $user = User::factory()->memberOf($workspace, $role)->create();

    $this->actingAs($user)->get(route('workspaces.index'))->assertForbidden();
})->with(WorkspaceRole::cases());

it('renames, trashes and restores a workspace', function () {
    [$owner, $workspace] = superAdminWithWorkspace();

    $component = Livewire::actingAs($owner)->test(Index::class)
        ->call('edit', $workspace->uuid)->set('form.name', 'Renombrado')->call('save');

    expect($workspace->fresh()->name)->toBe('Renombrado');

    $component->call('delete', $workspace->uuid);
    expect($workspace->fresh()->trashed())->toBeTrue()
        ->and($workspace->fresh()->deleted_by_id)->toBe($owner->id);

    $component->assertSeeText('En la papelera')->call('restore', $workspace->uuid);
    expect($workspace->fresh()->trashed())->toBeFalse();
});

it('cannot touch a workspace of another account', function () {
    [$owner] = superAdminWithWorkspace();
    $foreign = Workspace::factory()->create();

    Livewire::actingAs($owner)->test(Index::class)->call('delete', $foreign->uuid)->assertNotFound();

    expect($foreign->fresh()->trashed())->toBeFalse();
});

it('switches the current workspace from the sidebar', function () {
    [$owner, $first] = superAdminWithWorkspace();
    $second = Workspace::factory()->create(['account_id' => $first->account_id, 'name' => 'Segundo']);
    $foreign = Workspace::factory()->create();

    Livewire::actingAs($owner)
        ->test(WorkspaceSwitcher::class)
        ->assertSeeText('Segundo')
        ->call('switchTo', $second->uuid)
        ->assertRedirect(route('dashboard'));

    expect(session(CurrentWorkspace::SESSION_KEY))->toBe($second->id);

    Livewire::actingAs($owner)->test(WorkspaceSwitcher::class)->call('switchTo', $foreign->uuid)->assertNotFound();
});

it('lets an invited user see only the workspaces they were invited to (RN-03)', function () {
    [, $workspace] = superAdminWithWorkspace();
    Workspace::factory()->create(['name' => 'No invitado']);
    $member = User::factory()->memberOf($workspace)->create();

    Livewire::actingAs($member)
        ->test(WorkspaceSwitcher::class)
        ->assertSeeText($workspace->name)
        ->assertDontSeeText('No invitado');
});

it('hides the workspace picker from a client user and refuses switching', function () {
    [, $workspace] = superAdminWithWorkspace();
    $other = Workspace::factory()->create(['name' => 'Otro espacio']);
    $clientUser = User::factory()->memberOf($workspace, WorkspaceRole::Client)->create();
    User::factory()->memberOf($other)->create();
    $clientUser->workspaces()->attach($other, ['role' => WorkspaceRole::Member->value]);
    session([CurrentWorkspace::SESSION_KEY => $workspace->id]);

    Livewire::actingAs($clientUser)
        ->test(WorkspaceSwitcher::class)
        ->assertDontSeeText($workspace->name)
        ->assertDontSee('Cambiar espacio de trabajo')
        ->call('switchTo', $other->uuid)
        ->assertForbidden();

    expect(session(CurrentWorkspace::SESSION_KEY))->toBe($workspace->id);
});
