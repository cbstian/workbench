<?php

use App\Livewire\Portal\ProjectShow;
use App\Livewire\Portal\TaskPanel;
use App\Models\Page;
use App\Models\Task;
use Livewire\Livewire;

it('shows the project tasks to the client user in the three views (P-06)', function (string $view) {
    ['clientUser' => $clientUser, 'project' => $project, 'otherProject' => $otherProject] = clientPortalSetup();
    Task::factory()->for($project)->create(['name' => 'Otra tarea visible', 'starts_on' => today(), 'ends_on' => today()]);
    Task::factory()->for($otherProject)->create(['name' => 'Tarea ajena']);

    Livewire::actingAs($clientUser)->test(ProjectShow::class, ['project' => $project])
        ->call('setView', $view)
        ->assertSeeText('Otra tarea visible')
        ->assertDontSeeText('Tarea ajena');
})->with(['kanban', 'list', 'calendar']);

it('is read only: the board offers no way to move or edit tasks', function () {
    ['clientUser' => $clientUser, 'project' => $project] = clientPortalSetup();

    Livewire::actingAs($clientUser)->test(ProjectShow::class, ['project' => $project])
        ->assertDontSeeHtml('wire:sort')
        ->assertDontSeeText('Nueva tarea');
});

it('forbids projects not shared with the client user', function () {
    ['clientUser' => $clientUser, 'hiddenProject' => $hiddenProject, 'otherProject' => $otherProject] = clientPortalSetup();

    $this->actingAs($clientUser)->get(route('portal.projects.show', $hiddenProject))->assertForbidden();
    $this->actingAs($clientUser)->get(route('portal.projects.show', $otherProject))->assertForbidden();
});

it('opens a task of the project from the address, and ignores one of another project', function () {
    ['clientUser' => $clientUser, 'project' => $project, 'task' => $task, 'otherProject' => $otherProject] = clientPortalSetup();
    $foreign = Task::factory()->for($otherProject)->create();

    Livewire::withQueryParams(['tarea' => $task->uuid])->actingAs($clientUser)->test(ProjectShow::class, ['project' => $project])
        ->assertSet('showTask', true)
        ->assertSeeLivewire(TaskPanel::class);

    Livewire::actingAs($clientUser)->test(ProjectShow::class, ['project' => $project])
        ->call('openTask', $foreign->uuid)
        ->assertSet('showTask', false)
        ->assertSet('taskUuid', '');
});

it('lists only the project pages visible to the client, with visible ancestors', function () {
    ['clientUser' => $clientUser, 'project' => $project] = clientPortalSetup();
    $plan = Page::factory()->forProject($project)->visibleToClient()->create(['title' => 'Plan de trabajo']);
    Page::factory()->childOf($plan)->visibleToClient()->create(['title' => 'Entregables']);
    $internal = Page::factory()->forProject($project)->create(['title' => 'Notas internas']);
    Page::factory()->childOf($internal)->visibleToClient()->create(['title' => 'Hija de página interna']);

    Livewire::actingAs($clientUser)->test(ProjectShow::class, ['project' => $project])
        ->call('setView', ProjectShow::DOCS)
        ->assertSeeText('Plan de trabajo')
        ->assertSeeText('Entregables')
        ->assertDontSeeText('Notas internas')
        ->assertDontSeeText('Hija de página interna');
});
