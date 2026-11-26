<?php

use App\Actions\Comments\PostComment;
use App\Livewire\Notifications\Bell;
use App\Livewire\Notifications\Index;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Livewire\Livewire;

/**
 * The owner of two workspaces is mentioned on a task of the second one.
 *
 * @return array{owner: User, author: User, task: Task, first: Workspace, second: Workspace}
 */
function inboxSetup(): array
{
    [$owner, $first] = superAdminWithWorkspace();
    $second = Workspace::factory()->create(['account_id' => $first->account_id, 'name' => 'Segundo']);
    $project = Project::factory()->for($second)->create(['name' => 'Portal']);
    $author = User::factory()->memberOf($second)->create(['name' => 'Ana Autora']);
    $project->members()->attach($author);
    $task = Task::factory()->for($project)->create(['name' => 'Revisar contrato']);
    $task->assignees()->attach($author);

    app(PostComment::class)->handle($task, $author, "Mira esto @{$owner->name}");

    return compact('owner', 'author', 'task', 'first', 'second');
}

it('counts unread alerts in the bell and lists the latest', function () {
    ['owner' => $owner] = inboxSetup();

    Livewire::actingAs($owner)->test(Bell::class)
        ->assertSet('unreadCount', 1)
        ->assertSeeText('Ana Autora')
        ->assertSeeText('te mencionó en')
        ->assertSeeText('Revisar contrato')
        ->call('markAllAsRead')
        ->assertSet('unreadCount', 0)
        ->assertDispatched('notifications-changed');
});

it('filters the inbox and marks alerts read and unread', function () {
    ['owner' => $owner] = inboxSetup();
    $notification = $owner->notifications()->sole();

    $this->actingAs($owner)->get(route('notifications.index'))->assertOk()->assertSeeText('Revisar contrato');

    Livewire::actingAs($owner)->test(Index::class)
        ->set('kind', 'reply')
        ->assertDontSeeText('Revisar contrato')
        ->set('kind', 'mention')
        ->assertSeeText('Revisar contrato')
        ->call('markAsRead', $notification->id)
        ->assertDontSeeText('Revisar contrato')
        ->set('status', 'all')
        ->assertSeeText('Revisar contrato')
        ->call('markAsUnread', $notification->id);

    expect($notification->fresh()->read_at)->toBeNull();
});

it('opens an alert on the comment, switching to its workspace', function () {
    ['owner' => $owner, 'task' => $task, 'first' => $first, 'second' => $second] = inboxSetup();
    $notification = $owner->notifications()->sole();
    $comment = $task->comments()->sole();

    $this->actingAs($owner)
        ->withSession([CurrentWorkspace::SESSION_KEY => $first->id])
        ->get(route('notifications.open', $notification->id))
        ->assertRedirect(route('tasks.show', $task).'#comment-'.$comment->uuid)
        ->assertSessionHas(CurrentWorkspace::SESSION_KEY, $second->id);

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('does not open somebody else\'s alert', function () {
    ['owner' => $owner, 'author' => $author] = inboxSetup();

    $this->actingAs($author)->get(route('notifications.open', $owner->notifications()->sole()->id))->assertNotFound();
});

it('returns to the inbox when the task is gone', function () {
    ['owner' => $owner, 'task' => $task] = inboxSetup();
    $task->delete();

    $this->actingAs($owner)
        ->get(route('notifications.open', $owner->notifications()->sole()->id))
        ->assertRedirect(route('notifications.index'));
});
