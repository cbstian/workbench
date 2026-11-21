<?php

use App\Enums\Permission;
use App\Enums\WorkspaceRole;
use App\Livewire\Users\Index;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('redirects guests to the login screen', function () {
    $this->get(route('users.index'))->assertRedirect(route('login'));
});

it('lists the members of the current workspace with their role', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    User::factory()->memberOf($workspace, WorkspaceRole::Admin)->create(['name' => 'Ada Admin']);
    User::factory()->create(['name' => 'Fuera del espacio']);

    $this->actingAs($owner)
        ->get(route('users.index'))
        ->assertOk()
        ->assertSeeText('Ada Admin')
        ->assertSeeText('Admin')
        ->assertDontSeeText('Fuera del espacio');
});

it('creates an admin with access to projects (F-04)', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create();

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->call('create')
        ->set('form.name', 'Grace Hopper')
        ->set('form.email', 'grace@example.com')
        ->set('form.password', 'secreto123')
        ->set('form.role', 'admin')
        ->set('form.project_ids', [$project->id])
        ->call('save')
        ->assertHasNoErrors();

    $user = User::query()->where('email', 'grace@example.com')->sole();

    expect($user->account_id)->toBe($owner->ownedAccount->id)
        ->and(Hash::check('secreto123', $user->password))->toBeTrue()
        ->and($user->roleIn($workspace))->toBe(WorkspaceRole::Admin)
        ->and($user->isOnTeamOf($project))->toBeTrue()
        ->and($user->hasWorkspacePermission($workspace, Permission::MembersCreate))->toBeTrue();
});

it('creates a client user linked to several clients (RN-04)', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $clients = Client::factory()->for($workspace)->count(2)->sequence(['name' => 'Uno'], ['name' => 'Dos'])->create();

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->call('create')
        ->set('form.name', 'Cliente Persona')
        ->set('form.email', 'cliente@example.com')
        ->set('form.password', 'secreto123')
        ->set('form.role', 'client')
        ->set('form.client_ids', $clients->modelKeys())
        ->call('save')
        ->assertHasNoErrors();

    $user = User::query()->where('email', 'cliente@example.com')->sole();

    expect($user->roleIn($workspace))->toBe(WorkspaceRole::Client)
        ->and($user->clients()->pluck('clients.id')->sort()->values()->all())->toBe($clients->modelKeys());
});

it('validates the new user', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    User::factory()->create(['email' => 'tomado@example.com']);

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->call('create')
        ->set('form.email', 'tomado@example.com')
        ->set('form.role', 'client')
        ->call('save')
        ->assertHasErrors(['form.name', 'form.email', 'form.password', 'form.client_ids' => 'required']);
});

it('adds an existing user of the account (RN-26)', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $other = Workspace::factory()->create(['account_id' => $workspace->account_id]);
    $user = User::factory()->memberOf($other)->create(['account_id' => $workspace->account_id, 'name' => 'Ya existente']);
    $outsider = User::factory()->create();
    session([CurrentWorkspace::SESSION_KEY => $workspace->id]);

    $component = Livewire::actingAs($owner)->test(Index::class)->call('addExisting');

    expect(collect($component->instance()->addableUserOptions)->pluck('value')->all())->toBe([$user->id]);

    $component->set('form.existing_user_id', $outsider->id)->set('form.role', 'member')->call('save')
        ->assertHasErrors(['form.existing_user_id']);

    $component->set('form.existing_user_id', $user->id)->call('save')->assertHasNoErrors();

    expect($user->roleIn($workspace))->toBe(WorkspaceRole::Member);
});

it('changes a member into a client user, dropping their projects', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $member = User::factory()->memberOf($workspace)->create(['account_id' => $workspace->account_id]);
    $project = Project::factory()->for($workspace)->create();
    $project->members()->attach($member);
    $client = Client::factory()->for($workspace)->create();

    Livewire::actingAs($owner)
        ->test(Index::class)
        ->call('edit', $member->uuid)
        ->assertSet('form.project_ids', [$project->id])
        ->set('form.role', 'client')
        ->set('form.client_ids', [$client->id])
        ->call('save')
        ->assertHasNoErrors();

    expect($member->roleIn($workspace))->toBe(WorkspaceRole::Client)
        ->and($member->isOnTeamOf($project))->toBeFalse()
        ->and($member->hasWorkspacePermission($workspace, Permission::TasksView))->toBeFalse();
});

it('deactivates and reactivates access (A-09)', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $member = User::factory()->memberOf($workspace)->create(['account_id' => $workspace->account_id]);

    $component = Livewire::actingAs($owner)->test(Index::class)->call('toggleActive', $member->uuid);

    expect($member->fresh()->deactivated_at)->not->toBeNull()
        ->and($member->fresh()->deactivated_by_id)->toBe($owner->id);

    $component->call('toggleActive', $member->uuid);

    expect($member->fresh()->deactivated_at)->toBeNull();
});

it('deletes a user logically and restores it (A-10)', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $member = User::factory()->memberOf($workspace)->create(['account_id' => $workspace->account_id, 'name' => 'Borrable']);

    $component = Livewire::actingAs($owner)->test(Index::class)->call('delete', $member->uuid);

    expect(User::withTrashed()->find($member->id)->trashed())->toBeTrue();
    $component->assertSeeText('Usuarios eliminados')->call('restore', $member->uuid);

    expect($member->fresh()->trashed())->toBeFalse();
});

it('removes a user from the workspace and their spatie role', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $admin = User::factory()->memberOf($workspace, WorkspaceRole::Admin)->create(['account_id' => $workspace->account_id]);

    Livewire::actingAs($owner)->test(Index::class)->call('remove', $admin->uuid);

    expect($admin->roleIn($workspace))->toBeNull()
        ->and($admin->hasWorkspacePermission($workspace, Permission::ProjectsUpdate))->toBeFalse();
});

it('cannot manage users of another account', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $foreign = User::factory()->memberOf($workspace)->create();

    Livewire::actingAs($owner)->test(Index::class)->call('toggleActive', $foreign->uuid)->assertForbidden();
});

it('forbids members and client users (A-14)', function (WorkspaceRole $role) {
    [, $workspace] = superAdminWithWorkspace();
    $user = User::factory()->memberOf($workspace, $role)->create();

    $this->actingAs($user)->get(route('users.index'))->assertForbidden();
})->with([WorkspaceRole::Member, WorkspaceRole::Client]);

/**
 * An admin on one project of the workspace, plus a project they are not on.
 *
 * @return array{0: User, 1: Workspace, 2: Project, 3: Project}
 */
function adminWithProjects(): array
{
    [, $workspace] = superAdminWithWorkspace();
    $admin = User::factory()->memberOf($workspace, WorkspaceRole::Admin)->create(['account_id' => $workspace->account_id]);
    $own = Project::factory()->for($workspace)->create(['name' => 'Proyecto propio']);
    $foreign = Project::factory()->for($workspace)->create(['name' => 'Proyecto ajeno']);
    $own->members()->attach($admin);

    return [$admin, $workspace, $own, $foreign];
}

it('shows an admin only the teams of their projects', function () {
    [$admin, $workspace, $own, $foreign] = adminWithProjects();
    $teammate = User::factory()->memberOf($workspace)->create(['name' => 'Compañera']);
    $outsider = User::factory()->memberOf($workspace)->create(['name' => 'Otra persona']);
    $own->members()->attach($teammate);
    $foreign->members()->attach($outsider);

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertSeeText('Compañera')
        ->assertDontSeeText('Otra persona')
        ->assertSeeText('Nuevo miembro')
        ->assertDontSeeText('Agregar existente')
        ->assertDontSeeText('Desactivar acceso');
});

it('lets an admin create a member with access to their projects (RN-21)', function () {
    [$admin, $workspace, $own] = adminWithProjects();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('create')
        ->set('form.name', 'Nuevo Miembro')
        ->set('form.email', 'nuevo@example.com')
        ->set('form.password', 'secreto123')
        ->set('form.project_ids', [$own->id])
        ->call('save')
        ->assertHasNoErrors();

    $member = User::query()->where('email', 'nuevo@example.com')->sole();

    expect($member->roleIn($workspace))->toBe(WorkspaceRole::Member)
        ->and($member->account_id)->toBe($workspace->account_id)
        ->and($member->projectIdsIn($workspace))->toBe([$own->id])
        ->and(DB::table('workspace_user')->where('user_id', $member->id)->value('created_by_id'))->toBe($admin->id);
});

it('keeps an admin from creating anything but members of their projects (A-14)', function () {
    [$admin, , , $foreign] = adminWithProjects();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('create')
        ->set('form.name', 'Intento')
        ->set('form.email', 'intento@example.com')
        ->set('form.password', 'secreto123')
        ->set('form.role', 'admin')
        ->set('form.project_ids', [$foreign->id])
        ->call('save')
        ->assertHasErrors(['form.role' => 'in', 'form.project_ids.0' => 'in']);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('create')
        ->set('form.name', 'Intento')
        ->set('form.email', 'intento@example.com')
        ->set('form.password', 'secreto123')
        ->call('save')
        ->assertHasErrors(['form.project_ids' => 'required']);

    expect(User::query()->where('email', 'intento@example.com')->exists())->toBeFalse();
});

it('keeps an admin from adding existing users or managing accounts (A-21)', function () {
    [$admin, $workspace, $own] = adminWithProjects();
    $teammate = User::factory()->memberOf($workspace)->create(['account_id' => $workspace->account_id]);
    $own->members()->attach($teammate);

    Livewire::actingAs($admin)->test(Index::class)->call('addExisting')->assertForbidden();

    foreach (['edit', 'toggleActive', 'remove', 'delete'] as $method) {
        Livewire::actingAs($admin)->test(Index::class)->call($method, $teammate->uuid)->assertForbidden();
    }

    expect($teammate->fresh()->deactivated_at)->toBeNull();
});
