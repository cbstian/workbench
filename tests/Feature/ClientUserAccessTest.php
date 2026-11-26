<?php

use App\Actions\Tasks\StoreTaskAttachment;
use App\Livewire\Comments\Thread;
use App\Livewire\Projects\TaskBoard;
use App\Models\Client;
use App\Models\Page;
use App\Models\Project;
use App\Models\Task;
use App\Models\Workspace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('lets a client user sign in and sends them to their portal (F-11)', function () {
    ['clientUser' => $clientUser] = clientPortalSetup();

    $this->post(route('login.store'), ['email' => 'carla@example.com', 'password' => 'password'])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($clientUser);

    $this->get(route('dashboard'))->assertRedirect(route('portal.index'));
});

it('shows a client user only the portal in the menu', function () {
    ['clientUser' => $clientUser] = clientPortalSetup();

    $this->actingAs($clientUser)
        ->get(route('portal.index'))
        ->assertOk()
        ->assertSee(route('portal.index'), escape: false)
        ->assertSeeText('Mis proyectos')
        ->assertDontSee(route('projects.index'), escape: false)
        ->assertDontSee(route('tasks.index'), escape: false)
        ->assertDontSeeText('Administración');
});

it('forbids a client user the internal pages, even of what is shared with them (A-08)', function () {
    Storage::fake('local');
    ['owner' => $owner, 'clientUser' => $clientUser, 'project' => $project, 'task' => $task] = clientPortalSetup();
    $page = Page::factory()->forProject($project)->visibleToClient()->create();
    app(StoreTaskAttachment::class)->handle($task, $owner, UploadedFile::fake()->create('plan.pdf', 5));

    $this->actingAs($clientUser);

    foreach ([
        route('projects.index'),
        route('projects.show', $project),
        route('projects.settings', $project),
        route('projects.wiki', $project),
        route('tasks.index'),
        route('board.index'),
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

it('does not let a client user create or edit anything, nor read internal comments (A-03)', function () {
    ['clientUser' => $clientUser, 'project' => $project, 'task' => $task] = clientPortalSetup();

    Livewire::actingAs($clientUser)->test(TaskBoard::class, ['project' => $project])->assertForbidden();
    Livewire::actingAs($clientUser)->test(Thread::class, ['commentable' => $task])->assertForbidden();

    expect($clientUser->can('create', [Task::class, $project]))->toBeFalse()
        ->and($clientUser->can('update', $task))->toBeFalse()
        ->and($clientUser->can('comment', $task))->toBeFalse()
        ->and($clientUser->can('create', [Project::class, $project->workspace]))->toBeFalse()
        ->and($clientUser->can('create', [Client::class, $project->workspace]))->toBeFalse()
        ->and($clientUser->can('createMembers', $project->workspace))->toBeFalse()
        ->and($clientUser->can('create', Workspace::class))->toBeFalse();
});

it('keeps the client user own settings available', function () {
    ['clientUser' => $clientUser] = clientPortalSetup();

    $this->actingAs($clientUser)->get(route('settings.profile'))->assertOk();
});
