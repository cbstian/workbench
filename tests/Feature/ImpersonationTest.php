<?php

use App\Enums\ColorPalette;
use App\Enums\WorkspaceRole;
use App\Livewire\Clients\Index as ClientsIndex;
use App\Livewire\Settings\Profile;
use App\Livewire\Users\Index as UsersIndex;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use App\Support\Impersonation;
use Livewire\Livewire;

/**
 * A super admin with a member on one project and a client user.
 *
 * @return array{owner: User, workspace: Workspace, member: User, clientUser: User, project: Project}
 */
function impersonationSetup(): array
{
    [$owner, $workspace] = superAdminWithWorkspace(['name' => 'Sebastián Dueño']);
    $project = Project::factory()->for($workspace)->create(['name' => 'Proyecto del miembro']);
    Project::factory()->for($workspace)->create(['name' => 'Proyecto ajeno']);
    $member = User::factory()->memberOf($workspace)->create(['name' => 'Mario Miembro', 'account_id' => $workspace->account_id]);
    $project->members()->attach($member);
    $clientUser = User::factory()->memberOf($workspace, WorkspaceRole::Client)->create(['name' => 'Carla Cliente', 'account_id' => $workspace->account_id]);
    $clientUser->clients()->attach(Client::factory()->for($workspace)->create());

    return compact('owner', 'workspace', 'member', 'clientUser', 'project');
}

it('lets the super admin see the app as a member, with a bar to come back', function () {
    ['owner' => $owner, 'member' => $member] = impersonationSetup();

    $this->actingAs($owner)
        ->post(route('impersonation.start', $member->uuid))
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($member);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText('Estás viendo Workbench como')
        ->assertSeeText('Mario Miembro')
        ->assertSeeText('(Miembro)')
        ->assertSeeText('Volver a mi perfil')
        ->assertSeeText('Proyecto del miembro')
        ->assertDontSeeText('Proyecto ajeno');

    $this->get(route('users.index'))->assertForbidden();
});

it('lets the super admin see the app as a client user (E10)', function () {
    ['owner' => $owner, 'clientUser' => $clientUser] = impersonationSetup();

    $this->actingAs($owner)->post(route('impersonation.start', $clientUser->uuid));

    $this->get(route('dashboard'))->assertRedirect(route('portal.index'));

    $this->get(route('portal.index'))
        ->assertOk()
        ->assertSeeText('Carla Cliente')
        ->assertSeeText('(Cliente)');
});

it('starts from the users page', function () {
    ['owner' => $owner, 'member' => $member] = impersonationSetup();

    Livewire::actingAs($owner)->test(UsersIndex::class)
        ->assertSeeText('Ver como este usuario')
        ->call('impersonate', $member->uuid)
        ->assertRedirect(route('dashboard'));

    expect(auth()->id())->toBe($member->id)
        ->and(session(Impersonation::IMPERSONATOR_KEY))->toBe($owner->id);
});

it('starts from the clients page and comes back to it', function () {
    ['owner' => $owner, 'clientUser' => $clientUser] = impersonationSetup();
    $client = $clientUser->clients()->sole();

    Livewire::actingAs($owner)->test(ClientsIndex::class)
        ->assertSeeText('Ver como este cliente')
        ->call('impersonate', $client->uuid, $clientUser->uuid)
        ->assertRedirect(route('dashboard'));

    expect(auth()->id())->toBe($clientUser->id);

    $this->delete(route('impersonation.stop'))->assertRedirect(route('clients.index'));
    $this->assertAuthenticatedAs($owner);
});

it('only impersonates users linked to that client', function () {
    ['owner' => $owner, 'clientUser' => $clientUser, 'member' => $member, 'workspace' => $workspace] = impersonationSetup();
    $client = $clientUser->clients()->sole();
    $otherClient = Client::factory()->for($workspace)->create();

    Livewire::actingAs($owner)->test(ClientsIndex::class)
        ->call('impersonate', $otherClient->uuid, $clientUser->uuid)
        ->assertNotFound();

    Livewire::actingAs($owner)->test(ClientsIndex::class)
        ->call('impersonate', $client->uuid, $member->uuid)
        ->assertNotFound();

    $this->assertAuthenticatedAs($owner);
});

it('goes back to the super admin and their workspace', function () {
    ['owner' => $owner, 'member' => $member, 'workspace' => $workspace] = impersonationSetup();
    $second = Workspace::factory()->create(['account_id' => $workspace->account_id, 'name' => 'Zeta']);

    $this->actingAs($owner)
        ->withSession([CurrentWorkspace::SESSION_KEY => $second->id])
        ->post(route('impersonation.start', $member->uuid));

    $this->delete(route('impersonation.stop'))->assertRedirect(route('users.index'));

    $this->assertAuthenticatedAs($owner);
    expect(session(CurrentWorkspace::SESSION_KEY))->toBe($second->id)
        ->and(session()->has(Impersonation::IMPERSONATOR_KEY))->toBeFalse();

    $this->get(route('dashboard'))->assertDontSeeText('Estás viendo Workbench como');
});

it('cannot stop an impersonation that did not start', function () {
    ['owner' => $owner] = impersonationSetup();

    $this->actingAs($owner)->delete(route('impersonation.stop'))->assertForbidden();
    $this->assertAuthenticatedAs($owner);
});

it('only lets a super admin impersonate active users of their own account', function () {
    ['owner' => $owner, 'member' => $member, 'workspace' => $workspace] = impersonationSetup();
    [$otherOwner] = superAdminWithWorkspace();
    $deactivated = User::factory()->memberOf($workspace)->deactivated()->create(['account_id' => $workspace->account_id]);
    $admin = User::factory()->memberOf($workspace, WorkspaceRole::Admin)->create(['account_id' => $workspace->account_id]);

    $this->actingAs($otherOwner)->post(route('impersonation.start', $member->uuid))->assertForbidden();
    $this->actingAs($owner)->post(route('impersonation.start', $deactivated->uuid))->assertForbidden();
    $this->actingAs($owner)->post(route('impersonation.start', $owner->uuid))->assertForbidden();
    $this->actingAs($admin)->post(route('impersonation.start', $member->uuid))->assertForbidden();

    $this->assertAuthenticatedAs($admin);
});

it('does not chain impersonations', function () {
    ['owner' => $owner, 'member' => $member, 'clientUser' => $clientUser] = impersonationSetup();

    $this->actingAs($owner)->post(route('impersonation.start', $member->uuid));

    expect($owner->can('impersonate', $clientUser))->toBeFalse();
});

it('blocks account changes of the impersonated user', function () {
    ['owner' => $owner, 'member' => $member] = impersonationSetup();

    $this->actingAs($owner)->post(route('impersonation.start', $member->uuid));

    Livewire::test(Profile::class)
        ->set('profile.name', 'Otro nombre')
        ->call('saveProfile');

    expect($member->fresh()->name)->toBe('Mario Miembro');
});

it('does not change the palette of the impersonated user', function () {
    ['owner' => $owner, 'member' => $member] = impersonationSetup();

    $this->actingAs($owner)->post(route('impersonation.start', $member->uuid));

    Livewire::test(Profile::class)
        ->set('palette', 'bosque')
        ->assertSet('palette', 'roble')
        ->assertNotDispatched('palette-changed');

    expect($member->fresh()->palette)->toBe(ColorPalette::Roble);
});

it('brings the super admin back if the impersonated user is deactivated', function () {
    ['owner' => $owner, 'member' => $member] = impersonationSetup();

    $this->actingAs($owner)->post(route('impersonation.start', $member->uuid));
    $member->forceFill(['deactivated_at' => now()])->save();
    $this->app['auth']->forgetGuards();

    $this->get(route('dashboard'))->assertRedirect(route('users.index'));
    $this->assertAuthenticatedAs($owner);
});
