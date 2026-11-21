<?php

use App\Enums\TaskActivityEvent;
use App\Enums\WorkspaceRole;
use App\Livewire\Projects\Show as ProjectShow;
use App\Livewire\Tasks\Show;
use App\Livewire\Trash\Index;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Livewire\Livewire;

/**
 * A project with an admin and two members on its team.
 *
 * @return array{project: Project, workspace: Workspace, owner: User, admin: User, member: User, teammate: User}
 */
function projectWithTeam(): array
{
    [$owner, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create();
    $admin = User::factory()->memberOf($workspace, WorkspaceRole::Admin)->create(['name' => 'Ana Admin']);
    $member = User::factory()->memberOf($workspace)->create(['name' => 'Mario Miembro']);
    $teammate = User::factory()->memberOf($workspace)->create(['name' => 'Marta Miembro']);
    $project->members()->attach([$admin->id, $member->id, $teammate->id]);

    return compact('project', 'workspace', 'owner', 'admin', 'member', 'teammate');
}

it('lists the tasks of the project grouped by status', function () {
    ['project' => $project, 'member' => $member] = projectWithTeam();
    Task::factory()->for($project)->create(['name' => 'Tarea visible']);
    Task::factory()->subtaskOf(Task::factory()->for($project)->create(['name' => 'Padre']))->create(['name' => 'Hija oculta en la lista']);

    $this->actingAs($member)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertSeeText('Tarea visible')
        ->assertSeeText('Padre')
        ->assertSeeText('Backlog')
        ->assertDontSeeText('Hija oculta en la lista');
});

it('creates a task with the initial values of the project (RN-22)', function () {
    ['project' => $project, 'member' => $member, 'teammate' => $teammate] = projectWithTeam();
    $label = $project->taskLabels()->create(['name' => 'Bug']);

    Livewire::actingAs($member)
        ->test(ProjectShow::class, ['project' => $project])
        ->call('createTask')
        ->assertSet('form.status_id', $project->initialStatus->id)
        ->assertSet('form.assignee_ids', [$member->id])
        ->set('form.name', 'Preparar propuesta')
        ->set('form.assignee_ids', [$member->id, $teammate->id])
        ->set('form.label_ids', [$label->id])
        ->set('form.starts_on', '2026-11-01')
        ->set('form.ends_on', '2026-11-10')
        ->call('saveTask')
        ->assertHasNoErrors();

    $task = $project->tasks()->sole();

    expect($task->status_id)->toBe($project->initialStatus->id)
        ->and($task->priority_id)->toBe($project->initialPriority->id)
        ->and($task->created_by_id)->toBe($member->id)
        ->and($task->assignees()->pluck('users.id')->sort()->values()->all())->toBe([$member->id, $teammate->id])
        ->and($task->labels()->pluck('task_labels.id')->all())->toBe([$label->id])
        ->and($task->activities()->pluck('event')->all())->toBe([TaskActivityEvent::Created]);
});

it('validates the task (A-06, A-12, A-22)', function () {
    ['project' => $project, 'owner' => $owner, 'workspace' => $workspace] = projectWithTeam();
    $clientUser = User::factory()->memberOf($workspace, WorkspaceRole::Client)->create();
    $foreignStatus = Project::factory()->create()->initialStatus;

    Livewire::actingAs($owner)
        ->test(ProjectShow::class, ['project' => $project])
        ->call('createTask')
        ->set('form.name', '')
        ->set('form.status_id', $foreignStatus->id)
        ->set('form.starts_on', '2026-11-10')
        ->set('form.ends_on', '2026-11-01')
        ->set('form.assignee_ids', [$clientUser->id])
        ->call('saveTask')
        ->assertHasErrors(['form.name', 'form.status_id', 'form.ends_on', 'form.assignee_ids.0']);

    Livewire::actingAs($owner)
        ->test(ProjectShow::class, ['project' => $project])
        ->call('createTask')
        ->set('form.name', 'Sin estado')
        ->set('form.status_id', null)
        ->call('saveTask')
        ->assertHasErrors(['form.status_id' => 'required']);
});

it('forbids client users from seeing or creating tasks (A-03)', function () {
    ['project' => $project, 'workspace' => $workspace] = projectWithTeam();
    $clientUser = User::factory()->memberOf($workspace, WorkspaceRole::Client)->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAs($clientUser)->get(route('projects.show', $project))->assertForbidden();
    $this->actingAs($clientUser)->get(route('tasks.show', $task))->assertForbidden();
});

it('updates a task and records every change in its history (RN-08)', function () {
    ['project' => $project, 'admin' => $admin, 'member' => $member] = projectWithTeam();
    $task = Task::factory()->for($project)->create(['name' => 'Original']);
    $inProgress = $project->statuses()->where('name', 'En curso')->sole();
    $urgent = $project->priorities()->where('name', 'urgente')->sole();
    $label = $project->taskLabels()->create(['name' => 'Bug']);

    Livewire::actingAs($admin)
        ->test(Show::class, ['task' => $task])
        ->set('form.name', 'Renombrada')
        ->set('form.ends_on', '2026-12-01')
        ->set('form.status_id', $inProgress->id)
        ->set('form.priority_id', $urgent->id)
        ->set('form.assignee_ids', [$member->id])
        ->set('form.label_ids', [$label->id])
        ->call('save')
        ->assertHasNoErrors()
        ->assertSeeText('cambió el estado de «Backlog» a «En curso»')
        ->assertSeeText('asignó a Mario Miembro');

    $events = $task->activities()->pluck('event')->map->value->all();

    expect($task->fresh()->name)->toBe('Renombrada')
        ->and($events)->toBe(['updated', 'status_changed', 'priority_changed', 'assignee_added', 'label_added'])
        ->and($task->activities()->where('event', 'updated')->sole()->changes)->toEqual([
            'name' => ['old' => 'Original', 'new' => 'Renombrada'],
            'ends_on' => ['old' => null, 'new' => '2026-12-01'],
        ]);
});

it('closes a task when it moves to a completed status', function () {
    ['project' => $project, 'owner' => $owner] = projectWithTeam();
    $task = Task::factory()->for($project)->create();

    Livewire::actingAs($owner)
        ->test(Show::class, ['task' => $task])
        ->set('form.status_id', $project->statuses()->where('name', 'Hecho')->value('id'))
        ->call('save');

    expect($task->fresh()->completed_at)->not->toBeNull();
});

it('lets a member edit only the tasks assigned to them (RN-23, A-17)', function () {
    ['project' => $project, 'member' => $member] = projectWithTeam();
    $assigned = Task::factory()->for($project)->create();
    $assigned->assignees()->attach($member);
    $subtaskOfAssigned = Task::factory()->subtaskOf($assigned)->create();
    $other = Task::factory()->for($project)->create(['name' => 'Ajena']);

    $this->actingAs($member)->get(route('tasks.show', $other))->assertOk()->assertSeeText('solo la editan');

    Livewire::actingAs($member)->test(Show::class, ['task' => $other])->set('form.name', 'X')->call('save')->assertForbidden();
    Livewire::actingAs($member)->test(Show::class, ['task' => $other])->call('delete')->assertForbidden();
    Livewire::actingAs($member)->test(Show::class, ['task' => $assigned])->set('form.name', 'Mía')->call('save')->assertHasNoErrors();
    Livewire::actingAs($member)->test(Show::class, ['task' => $subtaskOfAssigned])->set('form.name', 'Subtarea mía')->call('save')->assertHasNoErrors();

    expect($other->fresh()->name)->toBe('Ajena')
        ->and($assigned->fresh()->name)->toBe('Mía')
        ->and($subtaskOfAssigned->fresh()->name)->toBe('Subtarea mía');
});

it('lets an admin edit any task of their project', function () {
    ['project' => $project, 'admin' => $admin] = projectWithTeam();
    $task = Task::factory()->for($project)->create();

    Livewire::actingAs($admin)->test(Show::class, ['task' => $task])->set('form.name', 'Editada por admin')->call('save')->assertHasNoErrors();

    expect($task->fresh()->name)->toBe('Editada por admin');
});

it('forbids users outside the project team (A-16)', function () {
    ['project' => $project, 'workspace' => $workspace] = projectWithTeam();
    $outsider = User::factory()->memberOf($workspace, WorkspaceRole::Admin)->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAs($outsider)->get(route('tasks.show', $task))->assertForbidden();
});

it('adds subtasks of one level only (RN-17, A-11)', function () {
    ['project' => $project, 'admin' => $admin] = projectWithTeam();
    $task = Task::factory()->for($project)->create();

    Livewire::actingAs($admin)
        ->test(Show::class, ['task' => $task])
        ->set('newSubtaskName', 'Paso uno')
        ->call('addSubtask')
        ->assertHasNoErrors()
        ->assertSeeText('Paso uno')
        ->assertSeeText('agregó la subtarea «Paso uno»');

    $subtask = $task->subtasks()->sole();

    expect($subtask->status_id)->toBe($project->initialStatus->id);

    Livewire::actingAs($admin)->test(Show::class, ['task' => $subtask])
        ->assertDontSeeText('Nueva subtarea')
        ->set('newSubtaskName', 'Nieta')
        ->call('addSubtask')
        ->assertForbidden();
});

it('sends a task to the trash with its history and restores it', function () {
    ['project' => $project, 'owner' => $owner] = projectWithTeam();
    $task = Task::factory()->for($project)->create();

    Livewire::actingAs($owner)->test(Show::class, ['task' => $task])
        ->call('delete')
        ->assertRedirect(route('projects.show', $project));

    expect($task->fresh()->trashed())->toBeTrue()
        ->and($task->fresh()->deleted_by_id)->toBe($owner->id);

    Livewire::actingAs($owner)->test(Index::class)->call('restore', 'task', $task->id);

    expect($task->fresh()->trashed())->toBeFalse()
        ->and($task->activities()->pluck('event')->map->value->all())->toBe(['deleted', 'restored']);
});

it('hides the tasks of a trashed project', function () {
    ['project' => $project, 'owner' => $owner] = projectWithTeam();
    $task = Task::factory()->for($project)->create();
    $project->delete();

    $this->actingAs($owner)->get(route('tasks.show', $task))->assertForbidden();
});
