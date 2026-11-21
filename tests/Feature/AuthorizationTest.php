<?php

use App\Actions\Authorization\SyncRolePermissions;
use App\Enums\Permission;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Spatie\Permission\Models\Role;

it('creates the global workspace roles with their permissions', function () {
    app(SyncRolePermissions::class)->handle();

    $roles = Role::query()->whereNull('team_id')->with('permissions')->get()->keyBy('name');

    expect($roles->keys()->sort()->values()->all())->toBe(['admin', 'client', 'member'])
        ->and($roles['client']->permissions)->toBeEmpty()
        ->and($roles['member']->permissions->pluck('name')->all())->toContain(Permission::TasksUpdateAssigned->value)
        ->and($roles['member']->permissions->pluck('name')->all())->not->toContain(Permission::TasksUpdateAny->value);
});

it('syncs the spatie role of a user per workspace with workspace_user.role (D-04)', function () {
    [, $first] = superAdminWithWorkspace();
    [, $second] = superAdminWithWorkspace();
    $user = User::factory()->memberOf($first, WorkspaceRole::Admin)->create();
    $second->members()->attach($user, ['role' => WorkspaceRole::Member]);

    expect($user->hasWorkspacePermission($first, Permission::ProjectsConfigure))->toBeTrue()
        ->and($user->hasWorkspacePermission($second, Permission::ProjectsConfigure))->toBeFalse();

    $second->members()->updateExistingPivot($user->id, ['role' => WorkspaceRole::Admin->value]);
    expect($user->hasWorkspacePermission($second, Permission::ProjectsConfigure))->toBeTrue();

    $first->members()->detach($user->id);
    expect($user->hasWorkspacePermission($first, Permission::ProjectsConfigure))->toBeFalse();
});

it('gives the super admin every ability in their own workspaces only', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $own = Project::factory()->for($workspace)->create();
    $foreign = Project::factory()->create();

    expect($owner->can('configure', $own))->toBeTrue()
        ->and($owner->can('manageTeam', $own))->toBeTrue()
        ->and($owner->can('create', [Project::class, $workspace]))->toBeTrue()
        ->and($owner->can('view', $foreign))->toBeFalse()
        ->and($owner->can('create', [Project::class, $foreign->workspace]))->toBeFalse()
        ->and($owner->can('create', Workspace::class))->toBeTrue();
});

it('lets the super admin of one account be a plain member elsewhere', function () {
    [$owner] = superAdminWithWorkspace();
    [, $other] = superAdminWithWorkspace();
    $other->members()->attach($owner, ['role' => WorkspaceRole::Member]);
    $project = Project::factory()->for($other)->create();
    $project->members()->attach($owner);

    expect($owner->can('view', $project))->toBeTrue()
        ->and($owner->can('configure', $project))->toBeFalse()
        ->and($owner->can('delete', $project))->toBeFalse();
});
