<?php

use App\Actions\Comments\PostComment;
use App\Enums\CommentThread;
use App\Livewire\Comments\Conversations;
use App\Livewire\Comments\Thread;
use App\Models\Page;
use App\Models\Task;
use App\Notifications\Comments\CommentOnFollowedItem;
use App\Notifications\Comments\MentionedInComment;
use App\Notifications\Comments\RepliedToYourComment;
use App\Support\ClientPortal;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

it('lets a client user write in the conversation with the client of a task and a page (P-04)', function () {
    Notification::fake();
    ['clientUser' => $clientUser, 'owner' => $owner, 'member' => $member, 'project' => $project, 'task' => $task] = clientPortalSetup();
    $page = Page::factory()->forProject($project)->visibleToClient()->create(['created_by_id' => $owner->id]);

    foreach ([$task, $page] as $commentable) {
        Livewire::actingAs($clientUser)->test(Thread::class, ['commentable' => $commentable, 'thread' => CommentThread::Client])
            ->assertSeeText('Conversación con el equipo')
            ->set('body', '¿Cómo va esto?')
            ->call('add')
            ->assertHasNoErrors();

        expect($commentable->comments()->sole())
            ->thread->toBe(CommentThread::Client)
            ->user_id->toBe($clientUser->id);
    }

    Notification::assertSentTo($owner, CommentOnFollowedItem::class);
    Notification::assertSentTo($member, CommentOnFollowedItem::class);
});

it('never alerts a client user of an internal comment, but does of a reply to them', function () {
    Notification::fake();
    ['clientUser' => $clientUser, 'owner' => $owner, 'task' => $task] = clientPortalSetup();
    $postComment = app(PostComment::class);

    $question = $postComment->handle($task, $clientUser, '¿Lo vemos el jueves?', thread: CommentThread::Client);
    $postComment->handle($task, $owner, 'Ojo con el presupuesto.');

    Notification::assertNothingSentTo($clientUser);

    $postComment->handle($task, $owner, 'Sí, el jueves.', replyTo: $question);

    Notification::assertSentTo($clientUser, RepliedToYourComment::class);
});

it('keeps a reply in the thread of its root', function () {
    ['owner' => $owner, 'task' => $task] = clientPortalSetup();
    $postComment = app(PostComment::class);

    $internal = $postComment->handle($task, $owner, 'Nota interna');
    $reply = $postComment->handle($task, $owner, 'Respuesta', replyTo: $internal, thread: CommentThread::Client);

    expect($reply->thread)->toBe(CommentThread::Internal);
});

it('lets client users and the team mention each other only in the client thread', function () {
    Notification::fake();
    ['clientUser' => $clientUser, 'member' => $member, 'task' => $task] = clientPortalSetup();

    expect($task->mentionableUsers(CommentThread::Client)->pluck('id'))->toContain($clientUser->id, $member->id)
        ->and($task->mentionableUsers()->pluck('id'))->not->toContain($clientUser->id);

    Livewire::actingAs($clientUser)->test(Thread::class, ['commentable' => $task, 'thread' => CommentThread::Client])
        ->set('body', 'Gracias @Mario Miembro')
        ->call('add');

    Notification::assertSentTo($member, MentionedInComment::class);
});

it('lets the team write to the client only while the task is shown to the client', function () {
    ['member' => $member, 'task' => $task, 'project' => $project] = clientPortalSetup();

    expect($member->can('commentInClientThread', $task))->toBeTrue();

    $project->update(['is_visible_to_client' => false]);

    expect($member->fresh()->can('commentInClientThread', $task->fresh()))->toBeFalse()
        ->and(app(ClientPortal::class)->isExposed($task->fresh()))->toBeFalse();
});

it('shows the team both conversations when the task is shown to the client', function () {
    ['member' => $member, 'task' => $task, 'hiddenProject' => $hiddenProject] = clientPortalSetup();
    app(PostComment::class)->handle($task, $member, 'Hola', thread: CommentThread::Client);

    Livewire::actingAs($member)->test(Conversations::class, ['commentable' => $task])
        ->assertSeeText('Con el cliente')
        ->assertSeeLivewire(Thread::class);

    $hiddenTask = Task::factory()->for($hiddenProject)->create();
    $hiddenProject->members()->attach($member);

    Livewire::actingAs($member)->test(Conversations::class, ['commentable' => $hiddenTask])
        ->assertDontSeeText('Con el cliente');
});

it('opens an alert in the portal for a client user and on the client tab for the team', function () {
    ['clientUser' => $clientUser, 'owner' => $owner, 'task' => $task] = clientPortalSetup();
    $postComment = app(PostComment::class);

    $question = $postComment->handle($task, $clientUser, '¿Novedades?', thread: CommentThread::Client);
    $answer = $postComment->handle($task, $owner, 'Mañana te cuento.', replyTo: $question);

    $this->actingAs($clientUser)
        ->get(route('notifications.open', $clientUser->notifications()->sole()->id))
        ->assertRedirect(route('portal.projects.show', ['project' => $task->project, 'tarea' => $task->uuid]).'#comment-'.$answer->uuid);

    $this->actingAs($owner)
        ->get(route('notifications.open', $owner->notifications()->sole()->id))
        ->assertRedirect(route('tasks.show', $task).'?hilo=cliente#comment-'.$question->uuid);
});
