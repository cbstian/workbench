<?php

use App\Enums\WorkspaceRole;
use App\Models\Account;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => $this->withoutVite())
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * A super admin (account owner) with one workspace of their account.
 *
 * @param  array<string, mixed>  $ownerAttributes
 * @return array{0: User, 1: Workspace}
 */
function superAdminWithWorkspace(array $ownerAttributes = []): array
{
    $owner = User::factory()->create($ownerAttributes);
    $workspace = Workspace::factory()->for(Account::factory()->for($owner, 'owner'))->create();

    return [$owner->fresh(), $workspace];
}

/**
 * A client user of «Andes Café» with a project shared with them (one task,
 * assigned to a team member), a project of the same client not shared, and
 * a shared project of another client.
 *
 * @return array{owner: User, workspace: Workspace, client: Client, clientUser: User, member: User, project: Project, task: Task, hiddenProject: Project, otherProject: Project}
 */
function clientPortalSetup(): array
{
    [$owner, $workspace] = superAdminWithWorkspace();
    $client = Client::factory()->for($workspace)->create(['name' => 'Andes Café']);
    $otherClient = Client::factory()->for($workspace)->create(['name' => 'Río Azul']);

    $project = Project::factory()->for($workspace)->for($client)->visibleToClient()->create(['name' => 'Portal del cliente']);
    $hiddenProject = Project::factory()->for($workspace)->for($client)->create(['name' => 'Proyecto reservado', 'is_visible_to_client' => false]);
    $otherProject = Project::factory()->for($workspace)->for($otherClient)->visibleToClient()->create(['name' => 'Proyecto ajeno']);

    $member = User::factory()->memberOf($workspace)->create(['name' => 'Mario Miembro']);
    $project->members()->attach($member);

    $task = Task::factory()->for($project)->create(['name' => 'Tarea del cliente', 'created_by_id' => $owner->id]);
    $task->assignees()->attach($member);

    $clientUser = User::factory()->memberOf($workspace, WorkspaceRole::Client)->create(['name' => 'Carla Cliente', 'email' => 'carla@example.com']);
    $clientUser->clients()->attach($client);

    return compact('owner', 'workspace', 'client', 'clientUser', 'member', 'project', 'task', 'hiddenProject', 'otherProject');
}
