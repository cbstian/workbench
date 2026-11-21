<?php

use App\Actions\Tasks\StoreTaskAttachment;
use App\Enums\TaskActivityEvent;
use App\Enums\WorkspaceRole;
use App\Livewire\Tasks\Attachments;
use App\Livewire\Tasks\Comments;
use App\Livewire\Tasks\Show;
use App\Livewire\Trash\Index as TrashIndex;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * A task assigned to a member, with an admin and another member on the team.
 *
 * @return array{task: Task, project: Project, owner: User, admin: User, member: User, teammate: User}
 */
function collaborationTask(): array
{
    [$owner, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create();
    $admin = User::factory()->memberOf($workspace, WorkspaceRole::Admin)->create();
    $member = User::factory()->memberOf($workspace)->create(['name' => 'Mario Miembro']);
    $teammate = User::factory()->memberOf($workspace)->create();
    $project->members()->attach([$admin->id, $member->id, $teammate->id]);
    $task = Task::factory()->for($project)->create();
    $task->assignees()->attach($member);

    return compact('task', 'project', 'owner', 'admin', 'member', 'teammate');
}

it('shows comments and attachments on the task page', function () {
    ['task' => $task, 'member' => $member] = collaborationTask();

    $this->actingAs($member)
        ->get(route('tasks.show', $task))
        ->assertOk()
        ->assertSeeLivewire(Comments::class)
        ->assertSeeLivewire(Attachments::class);
});

it('lets an assigned member comment, recorded in the history (F-07)', function () {
    ['task' => $task, 'member' => $member] = collaborationTask();

    Livewire::actingAs($member)
        ->test(Comments::class, ['task' => $task])
        ->set('body', "Primera línea\nSegunda línea")
        ->call('add')
        ->assertHasNoErrors()
        ->assertDispatched('task-activity-changed')
        ->assertSeeText('Primera línea');

    expect($task->comments()->sole()->user_id)->toBe($member->id)
        ->and($task->activities()->where('event', TaskActivityEvent::CommentAdded)->exists())->toBeTrue();

    Livewire::actingAs($member)->test(Show::class, ['task' => $task])->assertSeeText('comentó');
});

it('requires a comment body', function () {
    ['task' => $task, 'member' => $member] = collaborationTask();

    Livewire::actingAs($member)->test(Comments::class, ['task' => $task])
        ->set('body', '   ')
        ->call('add')
        ->assertHasErrors(['body' => 'required']);
});

it('keeps members off tasks not assigned to them, while they still see them (RN-23)', function () {
    ['task' => $task, 'teammate' => $teammate, 'member' => $member] = collaborationTask();
    $task->comments()->create(['user_id' => $member->id, 'body' => 'Visible para el equipo']);

    Livewire::actingAs($teammate)->test(Comments::class, ['task' => $task])
        ->assertSeeText('Visible para el equipo')
        ->assertDontSee('Escribe un comentario')
        ->set('body', 'No debería')
        ->call('add')
        ->assertForbidden();

    Livewire::actingAs($teammate)->test(Attachments::class, ['task' => $task])
        ->set('uploads', [UploadedFile::fake()->create('nota.txt', 1)])
        ->assertForbidden();
});

it('lets authors edit their comments and admins delete any', function () {
    ['task' => $task, 'member' => $member, 'admin' => $admin] = collaborationTask();
    $comment = $task->comments()->create(['user_id' => $member->id, 'body' => 'Original']);

    Livewire::actingAs($member)->test(Comments::class, ['task' => $task])
        ->call('edit', $comment->id)
        ->set('editingBody', 'Corregido')
        ->call('saveEdit')
        ->assertSeeText('Corregido')
        ->assertSeeText('editado');

    expect($comment->fresh()->edited_at)->not->toBeNull();

    Livewire::actingAs($admin)->test(Comments::class, ['task' => $task])->call('edit', $comment->id)->assertNotFound();
    Livewire::actingAs($admin)->test(Comments::class, ['task' => $task])->call('delete', $comment->id);

    expect($comment->fresh()->trashed())->toBeTrue()
        ->and($comment->fresh()->deleted_by_id)->toBe($admin->id)
        ->and($task->activities()->where('event', TaskActivityEvent::CommentDeleted)->exists())->toBeTrue();
});

it('does not let a member delete another person\'s comment', function () {
    ['task' => $task, 'member' => $member, 'teammate' => $teammate] = collaborationTask();
    $task->assignees()->attach($teammate);
    $comment = $task->comments()->create(['user_id' => $teammate->id, 'body' => 'Ajeno']);

    Livewire::actingAs($member)->test(Comments::class, ['task' => $task])->call('delete', $comment->id)->assertForbidden();

    expect($comment->fresh()->trashed())->toBeFalse();
});

it('uploads attachments to the private disk and records them', function () {
    Storage::fake('local');
    ['task' => $task, 'member' => $member] = collaborationTask();

    Livewire::actingAs($member)->test(Attachments::class, ['task' => $task])
        ->set('uploads', [UploadedFile::fake()->create('contrato.pdf', 120, 'application/pdf')])
        ->assertHasNoErrors()
        ->assertSeeText('contrato.pdf');

    $attachment = $task->attachments()->sole();

    expect($attachment->original_name)->toBe('contrato.pdf')
        ->and($attachment->uploaded_by_id)->toBe($member->id)
        ->and($attachment->path)->toBe("workspaces/{$task->project->workspace->uuid}/tasks/{$task->uuid}/{$attachment->uuid}")
        ->and($attachment->checksum_sha256)->toHaveLength(64)
        ->and($task->activities()->where('event', TaskActivityEvent::AttachmentAdded)->sole()->description())->toBe('adjuntó «contrato.pdf»');

    Storage::disk('local')->assertExists($attachment->path);
});

it('rejects files over 10 MB', function () {
    Storage::fake('local');
    ['task' => $task, 'member' => $member] = collaborationTask();

    Livewire::actingAs($member)->test(Attachments::class, ['task' => $task])
        ->set('uploads', [UploadedFile::fake()->create('grande.zip', 10241)])
        ->assertHasErrors(['uploads.0']);

    expect($task->attachments()->exists())->toBeFalse();
});

it('downloads an attachment only for people who see the task', function () {
    Storage::fake('local');
    ['task' => $task, 'member' => $member, 'project' => $project] = collaborationTask();
    $attachment = app(StoreTaskAttachment::class)
        ->handle($task, $member, UploadedFile::fake()->createWithContent('nota.txt', 'hola'));
    $outsider = User::factory()->memberOf($project->workspace)->create();
    $clientUser = User::factory()->memberOf($project->workspace, WorkspaceRole::Client)->create();

    $this->actingAs($member)->get(route('attachments.show', $attachment))
        ->assertOk()
        ->assertDownload('nota.txt');

    $this->actingAs($outsider)->get(route('attachments.show', $attachment))->assertForbidden();
    $this->actingAs($clientUser)->get(route('attachments.show', $attachment))->assertForbidden();
});

it('sends an attachment to the trash keeping the file, and restores it', function () {
    Storage::fake('local');
    ['task' => $task, 'member' => $member, 'owner' => $owner] = collaborationTask();
    $attachment = app(StoreTaskAttachment::class)
        ->handle($task, $member, UploadedFile::fake()->create('plano.png', 10));

    Livewire::actingAs($member)->test(Attachments::class, ['task' => $task])->call('delete', $attachment->id);

    expect($attachment->fresh()->trashed())->toBeTrue();
    Storage::disk('local')->assertExists($attachment->path);

    Livewire::actingAs($owner)->test(TrashIndex::class)->call('restore', 'task_attachment', $attachment->id);

    expect($attachment->fresh()->trashed())->toBeFalse();
});

it('does not serve attachments that are in the trash', function () {
    Storage::fake('local');
    ['task' => $task, 'member' => $member] = collaborationTask();
    $attachment = app(StoreTaskAttachment::class)
        ->handle($task, $member, UploadedFile::fake()->create('viejo.txt', 1));
    $attachment->delete();

    $this->actingAs($member)->get(route('attachments.show', $attachment))->assertNotFound();
});
