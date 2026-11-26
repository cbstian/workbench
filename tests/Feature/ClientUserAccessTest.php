<?php

use App\Actions\Tasks\StoreTaskAttachment;
use App\Enums\WorkspaceRole;
use App\Livewire\Dashboard;
use App\Livewire\Projects\TaskBoard;
use App\Livewire\Tasks\Comments;
use App\Models\Client;
use App\Models\Page;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * A client user of «Andes Café», whose project is marked visible to the client
 * and has a task, a page and an attachment.
 *
 * @return array{clientUser: User, project: Project, task: Task, page: Page, workspace: Workspace}
 */
function clientUserSetup(): array
{
    Storage::fake('local');
    [$owner, $workspace] = superAdminWithWorkspace();
    $client = Client::factory()->for($workspace)->create(['name' => 'Andes Café']);
    $project = Project::factory()->for($workspace)->for($client)->visibleToClient()->create(['name' => 'Portal del cliente']);
    $task = Task::factory()->for($project)->create(['name' => 'Tarea del cliente']);
    $page = Page::factory()->forProject($project)->create(['title' => 'Documentación visible', 'is_visible_to_client' => true]);
    app(StoreTaskAttachment::class)->handle($task, $owner, UploadedFile::fake()->create('plan.pdf', 5));

    $clientUser = User::factory()->memberOf($workspace, WorkspaceRole::Client)->create(['name' => 'Carla Cliente', 'email' => 'carla@example.com']);
    $clientUser->clients()->attach($client);

    return compact('clientUser', 'project', 'task', 'page', 'workspace');
}

it('lets a client user sign in (F-11)', function () {
    ['clientUser' => $clientUser] = clientUserSetup();

    $this->post(route('login.store'), ['email' => 'carla@example.com', 'password' => 'password'])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($clientUser);
});

it('shows a client user nothing, even what is marked visible to their client (A-08)', function () {
    ['clientUser' => $clientUser] = clientUserSetup();

    $this->actingAs($clientUser)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText('Por ahora no hay contenido para ti')
        ->assertSeeText('como cliente de Andes Café')
        ->assertDontSeeText('Portal del cliente')
        ->assertDontSeeText('Tarea del cliente')
        ->assertDontSeeText('Documentación visible')
        ->assertDontSee(route('projects.index'), escape: false)
        ->assertDontSeeText('Administración');

    $dashboard = Livewire::actingAs($clientUser)->test(Dashboard::class)->instance();

    expect($dashboard->projects)->toBeEmpty()
        ->and(collect($dashboard->stats)->sum('value'))->toBe(0);
});

it('forbids a client user every page of the workspace content', function () {
    ['clientUser' => $clientUser, 'project' => $project, 'task' => $task, 'page' => $page] = clientUserSetup();

    $this->actingAs($clientUser);

    foreach ([
        route('projects.index'),
        route('projects.show', $project),
        route('projects.settings', $project),
        route('projects.wiki', $project),
        route('tasks.show', $task),
        route('pages.show', $page),
        route('attachments.show', $task->attachments()->sole()),
        route('wiki.index'),
        route('clients.index'),
        route('users.index'),
        route('trash.index'),
        route('workspaces.index'),
        route('roles.index'),
    ] as $url) {
        $this->get($url)->assertForbidden();
    }
});

it('does not let a client user create or edit anything (A-03)', function () {
    ['clientUser' => $clientUser, 'project' => $project, 'task' => $task] = clientUserSetup();

    Livewire::actingAs($clientUser)->test(TaskBoard::class, ['project' => $project])->assertForbidden();
    Livewire::actingAs($clientUser)->test(Comments::class, ['task' => $task])->assertForbidden();

    expect($clientUser->can('create', [Task::class, $project]))->toBeFalse()
        ->and($clientUser->can('update', $task))->toBeFalse()
        ->and($clientUser->can('create', [Project::class, $project->workspace]))->toBeFalse()
        ->and($clientUser->can('create', [Client::class, $project->workspace]))->toBeFalse()
        ->and($clientUser->can('createMembers', $project->workspace))->toBeFalse()
        ->and($clientUser->can('create', Workspace::class))->toBeFalse();
});

it('keeps the client user own settings available', function () {
    ['clientUser' => $clientUser] = clientUserSetup();

    $this->actingAs($clientUser)->get(route('settings.profile'))->assertOk();
});
