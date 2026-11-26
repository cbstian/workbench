<?php

use App\Enums\PageActivityEvent;
use App\Enums\WorkspaceRole;
use App\Livewire\Comments\Thread;
use App\Livewire\Projects\TaskBoard;
use App\Livewire\Trash\Index as TrashIndex;
use App\Models\Page;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\Comments\CommentOnFollowedItem;
use App\Notifications\Comments\MentionedInComment;
use App\Notifications\Comments\RepliedToYourComment;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

/**
 * A project with an assigned member, an unassigned teammate and an outsider
 * who is in the workspace but not on the project team.
 *
 * @return array{owner: User, project: Project, task: Task, member: User, teammate: User, outsider: User}
 */
function threadSetup(): array
{
    [$owner, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create();
    $member = User::factory()->memberOf($workspace)->create(['name' => 'Mario Miembro']);
    $teammate = User::factory()->memberOf($workspace)->create(['name' => 'Tania Torres']);
    $outsider = User::factory()->memberOf($workspace, WorkspaceRole::Admin)->create(['name' => 'Olga Otra']);
    $project->members()->attach([$member->id, $teammate->id]);
    $task = Task::factory()->for($project)->create(['created_by_id' => $owner->id]);
    $task->assignees()->attach($member);

    return compact('owner', 'project', 'task', 'member', 'teammate', 'outsider');
}

it('stores mentions of people who can see the task as tokens and alerts them (RN-10)', function () {
    Notification::fake();
    ['task' => $task, 'member' => $member, 'teammate' => $teammate, 'outsider' => $outsider] = threadSetup();

    Livewire::actingAs($member)->test(Thread::class, ['commentable' => $task])
        ->set('body', '¿Lo revisas @tania torres? Copio a @Olga Otra')
        ->call('add')
        ->assertHasNoErrors()
        ->assertSeeHtml('@Tania Torres</span>');

    $comment = $task->comments()->sole();

    expect($comment->body)->toBe("¿Lo revisas @[{$teammate->uuid}]? Copio a @Olga Otra")
        ->and($comment->mentions->pluck('id')->all())->toBe([$teammate->id]);

    Notification::assertSentTo($teammate, MentionedInComment::class);
    Notification::assertNotSentTo($outsider, MentionedInComment::class);
    Notification::assertNotSentTo($member, MentionedInComment::class);
});

it('alerts followers once each, preferring the most specific alert, never the author', function () {
    Notification::fake();
    ['task' => $task, 'owner' => $owner, 'member' => $member, 'teammate' => $teammate] = threadSetup();

    Livewire::actingAs($member)->test(Thread::class, ['commentable' => $task])
        ->set('body', 'Avance listo, @Tania Torres')
        ->call('add');

    Notification::assertSentTo($owner, CommentOnFollowedItem::class);
    Notification::assertSentToTimes($teammate, MentionedInComment::class, 1);
    Notification::assertNotSentTo($teammate, CommentOnFollowedItem::class);
    Notification::assertNothingSentTo($member);
});

it('keeps replies one level deep and alerts the thread participants', function () {
    Notification::fake();
    ['task' => $task, 'owner' => $owner, 'member' => $member] = threadSetup();
    $root = $task->comments()->create(['user_id' => $member->id, 'body' => 'Pregunta']);
    $reply = $task->comments()->create(['user_id' => $owner->id, 'parent_id' => $root->id, 'body' => 'Respuesta']);
    $task->setFollowing($member, false);

    Livewire::actingAs($owner)->test(Thread::class, ['commentable' => $task])
        ->call('reply', $reply->id)
        ->assertSet('replyingToId', $root->id)
        ->set('replyBody', 'Una cosa más')
        ->call('addReply')
        ->assertHasNoErrors()
        ->assertDispatched('task-activity-changed');

    expect($root->replies()->count())->toBe(2);
    Notification::assertSentTo($member, RepliedToYourComment::class);
});

it('refuses a reply to a reply at the database level', function () {
    ['task' => $task, 'member' => $member] = threadSetup();
    $root = $task->comments()->create(['user_id' => $member->id, 'body' => 'Raíz']);
    $reply = $task->comments()->create(['user_id' => $member->id, 'parent_id' => $root->id, 'body' => 'Hijo']);

    expect(fn () => $task->comments()->create(['user_id' => $member->id, 'parent_id' => $reply->id, 'body' => 'Nieto']))
        ->toThrow(QueryException::class);
});

it('lets people unfollow, and then only mentions reach them', function () {
    Notification::fake();
    ['task' => $task, 'owner' => $owner, 'member' => $member] = threadSetup();

    Livewire::actingAs($owner)->test(Thread::class, ['commentable' => $task])
        ->assertSee('Dejar de seguir')
        ->call('toggleFollow')
        ->assertSee('Seguir');

    Livewire::actingAs($member)->test(Thread::class, ['commentable' => $task])
        ->set('body', 'Sin mención')
        ->call('add');

    Notification::assertNothingSentTo($owner);
    expect($task->followerIds())->not->toContain($owner->id);
});

it('alerts only newly mentioned people when a comment is edited', function () {
    Notification::fake();
    ['task' => $task, 'owner' => $owner, 'member' => $member, 'teammate' => $teammate] = threadSetup();
    $task->setFollowing($owner, false);

    $thread = Livewire::actingAs($member)->test(Thread::class, ['commentable' => $task])
        ->set('body', 'Hola @Tania Torres')
        ->call('add');

    $comment = $task->comments()->sole();

    $thread->call('edit', $comment->id)
        ->assertSet('editingBody', 'Hola @Tania Torres')
        ->set('editingBody', 'Hola @Tania Torres y @'.$owner->name)
        ->call('saveEdit')
        ->assertHasNoErrors();

    Notification::assertSentToTimes($teammate, MentionedInComment::class, 1);
    Notification::assertSentTo($owner, MentionedInComment::class);
});

it('keeps a deleted root visible while it has replies', function () {
    ['task' => $task, 'owner' => $owner, 'member' => $member] = threadSetup();
    $root = $task->comments()->create(['user_id' => $member->id, 'body' => 'Raíz borrable']);
    $task->comments()->create(['user_id' => $owner->id, 'parent_id' => $root->id, 'body' => 'Sigue aquí']);

    Livewire::actingAs($member)->test(Thread::class, ['commentable' => $task])
        ->call('delete', $root->id)
        ->assertSeeText('Comentario eliminado')
        ->assertSeeText('Sigue aquí')
        ->assertDontSeeText('Raíz borrable');
});

it('lets whoever reaches a page comment on it and send comments to the trash', function () {
    Notification::fake();
    ['owner' => $owner, 'project' => $project, 'member' => $member, 'outsider' => $outsider] = threadSetup();
    $page = Page::factory()->forProject($project)->create(['created_by_id' => $owner->id, 'title' => 'Manual']);

    $this->actingAs($member)->get(route('pages.show', $page))->assertOk()->assertSeeLivewire(Thread::class);

    Livewire::actingAs($member)->test(Thread::class, ['commentable' => $page])
        ->set('body', 'Falta un paso')
        ->call('add')
        ->assertDispatched('page-activity-changed');

    $comment = $page->comments()->sole();

    expect($page->activities()->where('event', PageActivityEvent::CommentAdded)->exists())->toBeTrue();
    Notification::assertSentTo($owner, CommentOnFollowedItem::class);

    Livewire::actingAs($outsider)->test(Thread::class, ['commentable' => $page])->assertForbidden();

    Livewire::actingAs($member)->test(Thread::class, ['commentable' => $page])->call('delete', $comment->id);
    expect($comment->fresh()->trashed())->toBeTrue();

    Livewire::actingAs($owner)->test(TrashIndex::class)
        ->assertSeeText('Falta un paso')
        ->call('restore', 'page_comment', $comment->id);

    expect($comment->fresh()->trashed())->toBeFalse();
});

it('does not let a member delete another person\'s page comment', function () {
    ['owner' => $owner, 'project' => $project, 'member' => $member] = threadSetup();
    $page = Page::factory()->forProject($project)->create();
    $comment = $page->comments()->create(['user_id' => $owner->id, 'body' => 'Del dueño']);

    Livewire::actingAs($member)->test(Thread::class, ['commentable' => $page])->call('delete', $comment->id)->assertForbidden();

    expect($comment->fresh()->trashed())->toBeFalse();
});

it('shows comments in the task side panel', function () {
    ['task' => $task, 'project' => $project, 'member' => $member] = threadSetup();

    Livewire::actingAs($member)->test(TaskBoard::class, ['project' => $project])
        ->call('editTask', $task->id)
        ->assertSeeLivewire(Thread::class);
});
