<?php

use App\Actions\Comments\PostComment;
use App\Actions\Tasks\StoreTaskAttachment;
use App\Enums\CommentThread;
use App\Livewire\Portal\TaskPanel;
use App\Models\Task;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('shows the task data, assignees, subtasks and conversation, but no attachments (P-04)', function () {
    Storage::fake('local');
    ['clientUser' => $clientUser, 'owner' => $owner, 'task' => $task] = clientPortalSetup();
    Task::factory()->subtaskOf($task)->create(['name' => 'Subtarea visible']);
    app(StoreTaskAttachment::class)->handle($task, $owner, UploadedFile::fake()->create('presupuesto.pdf', 5));

    Livewire::actingAs($clientUser)->test(TaskPanel::class, ['task' => $task])
        ->assertSeeText('Tarea del cliente')
        ->assertSeeText('Mario Miembro')
        ->assertSeeText('Subtarea visible')
        ->assertDontSeeText('presupuesto.pdf');
});

it('keeps internal comments out of the conversation and the history', function () {
    ['clientUser' => $clientUser, 'owner' => $owner, 'task' => $task] = clientPortalSetup();
    $postComment = app(PostComment::class);
    $postComment->handle($task, $owner, 'Comentario solo del equipo');
    $postComment->handle($task, $owner, 'Mensaje para el cliente', thread: CommentThread::Client);

    $panel = Livewire::actingAs($clientUser)->test(TaskPanel::class, ['task' => $task])
        ->assertSeeText('Mensaje para el cliente')
        ->assertDontSeeText('Comentario solo del equipo');

    expect($panel->instance()->activities->pluck('metadata.comment_id')->filter()->all())
        ->toBe([$task->comments()->where('thread', CommentThread::Client)->value('id')]);
});

it('forbids a task of a project not shared with the client user', function () {
    ['clientUser' => $clientUser, 'hiddenProject' => $hiddenProject] = clientPortalSetup();

    Livewire::actingAs($clientUser)->test(TaskPanel::class, ['task' => Task::factory()->for($hiddenProject)->create()])
        ->assertForbidden();
});
