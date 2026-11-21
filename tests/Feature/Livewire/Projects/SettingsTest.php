<?php

use App\Enums\StatusCategory;
use App\Enums\TaskActivityEvent;
use App\Enums\WorkspaceRole;
use App\Livewire\Projects\Settings;
use App\Livewire\Projects\TaskLabels;
use App\Livewire\Projects\WorkflowValues;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Livewire\Livewire;

it('renders the settings page for the super admin', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create();

    $this->actingAs($owner)
        ->get(route('projects.settings', $project))
        ->assertOk()
        ->assertSeeLivewire(Settings::class)
        ->assertSeeLivewire(WorkflowValues::class)
        ->assertSeeLivewire(TaskLabels::class)
        ->assertSeeText('Zona de peligro');
});

it('lets an assigned admin configure but not manage the team or delete (RN-21, RN-26)', function () {
    [, $workspace] = superAdminWithWorkspace();
    $admin = User::factory()->memberOf($workspace, WorkspaceRole::Admin)->create();
    $project = Project::factory()->for($workspace)->create();
    $project->members()->attach($admin);

    $this->actingAs($admin)->get(route('projects.settings', $project))->assertOk()->assertDontSeeText('Zona de peligro');

    Livewire::actingAs($admin)->test(Settings::class, ['project' => $project])->call('addMembers')->assertForbidden();
});

it('forbids members and unassigned admins', function () {
    [, $workspace] = superAdminWithWorkspace();
    $member = User::factory()->memberOf($workspace)->create();
    $admin = User::factory()->memberOf($workspace, WorkspaceRole::Admin)->create();
    $project = Project::factory()->for($workspace)->create();
    $project->members()->attach($member);

    $this->actingAs($member)->get(route('projects.settings', $project))->assertForbidden();
    $this->actingAs($admin)->get(route('projects.settings', $project))->assertForbidden();
});

it('updates the general data', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create();

    Livewire::actingAs($owner)
        ->test(Settings::class, ['project' => $project])
        ->set('form.name', 'Nuevo nombre')
        ->set('form.is_visible_to_client', true)
        ->call('saveGeneral')
        ->assertHasNoErrors()
        ->assertDispatched('projects-changed');

    expect($project->fresh())->name->toBe('Nuevo nombre')->is_visible_to_client->toBeTrue();
});

it('adds existing admins and members to the team and removes them', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $member = User::factory()->memberOf($workspace)->create();
    $clientUser = User::factory()->memberOf($workspace, WorkspaceRole::Client)->create();
    $project = Project::factory()->for($workspace)->create();

    $component = Livewire::actingAs($owner)->test(Settings::class, ['project' => $project]);

    expect(collect($component->instance()->candidateOptions)->pluck('value')->all())->toBe([$member->id]);

    $component->set('newMemberIds', [$clientUser->id])->call('addMembers')->assertHasErrors(['newMemberIds.0']);
    $component->set('newMemberIds', [$member->id])->call('addMembers')->assertHasNoErrors();

    expect($member->isOnTeamOf($project))->toBeTrue();

    $component->call('removeMember', $member->id);

    expect($member->isOnTeamOf($project))->toBeFalse();
});

it('adds, edits, reorders and changes the initial status', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create();

    $component = Livewire::actingAs($owner)
        ->test(WorkflowValues::class, ['project' => $project, 'type' => 'statuses'])
        ->call('create')
        ->set('name', 'Revisión')
        ->set('category', 'started')
        ->set('color', '#AA00CC')
        ->call('save')
        ->assertHasNoErrors();

    $review = $project->statuses()->where('name', 'Revisión')->sole();
    expect($review->category)->toBe(StatusCategory::Started)->and($review->position)->toBe(6144);

    $component->call('move', $review->id, -1);
    expect($project->statuses()->pluck('name')->all())->toBe(['Backlog', 'Por hacer', 'En curso', 'Hecho', 'Revisión', 'Cancelado']);

    $component->call('makeInitial', $review->id);
    expect($project->initialStatus()->sole()->id)->toBe($review->id);

    $component->call('create')->set('name', 'backlog')->call('save')->assertHasErrors(['name']);
});

it('moves tasks to the initial status when a status is deleted (A-13)', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create();
    $inProgress = $project->statuses()->where('name', 'En curso')->sole();
    $task = Task::factory()->for($project)->create(['status_id' => $inProgress->id]);

    Livewire::actingAs($owner)
        ->test(WorkflowValues::class, ['project' => $project, 'type' => 'statuses'])
        ->call('delete', $inProgress->id);

    expect($inProgress->fresh()->trashed())->toBeTrue()
        ->and($task->fresh()->status_id)->toBe($project->initialStatus->id)
        ->and($task->activities()->where('event', TaskActivityEvent::StatusChanged)->sole()->metadata)->toBe(['reason' => 'status_deleted']);
});

it('does not delete the initial value (A-23)', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create();

    Livewire::actingAs($owner)
        ->test(WorkflowValues::class, ['project' => $project, 'type' => 'priorities'])
        ->call('delete', $project->initialPriority->id)
        ->assertHasErrors(['value']);

    expect($project->initialPriority()->exists())->toBeTrue();
});

it('manages task labels', function () {
    [$owner, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create();

    $component = Livewire::actingAs($owner)
        ->test(TaskLabels::class, ['project' => $project])
        ->call('create')->set('name', 'Bug')->set('color', '#ff0000')->call('save')
        ->assertHasNoErrors()
        ->assertSeeText('Bug');

    $label = $project->taskLabels()->sole();

    $component->call('edit', $label->id)->set('name', 'Error')->call('save');
    expect($label->fresh()->name)->toBe('Error');

    $component->call('delete', $label->id);
    expect($label->fresh()->trashed())->toBeTrue();
});
