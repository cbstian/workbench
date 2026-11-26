<?php

use App\Enums\StatusCategory;
use App\Livewire\Portal\Home;
use App\Models\Page;
use App\Models\Task;
use Livewire\Livewire;

it('lists only the projects of the client user\'s clients marked visible (RN-05)', function () {
    ['clientUser' => $clientUser] = clientPortalSetup();

    $this->actingAs($clientUser)
        ->get(route('portal.index'))
        ->assertOk()
        ->assertSeeText('Andes Café')
        ->assertSeeText('Portal del cliente')
        ->assertDontSeeText('Proyecto reservado')
        ->assertDontSeeText('Proyecto ajeno');
});

it('shows the progress, the upcoming due dates and the shared workspace documentation', function () {
    ['clientUser' => $clientUser, 'project' => $project, 'task' => $task, 'workspace' => $workspace] = clientPortalSetup();
    $task->update(['ends_on' => today()->addDays(3)]);
    Task::factory()->for($project)->create(['status_id' => $project->statuses()->where('category', StatusCategory::Completed)->value('id')]);
    Page::factory()->for($workspace)->visibleToClient()->create(['title' => 'Cómo trabajamos']);
    Page::factory()->for($workspace)->create(['title' => 'Notas del equipo']);

    Livewire::actingAs($clientUser)->test(Home::class)
        ->assertSeeText('1 de 2 tareas cerradas')
        ->assertSeeText('Tarea del cliente')
        ->assertSeeText('Cómo trabajamos')
        ->assertDontSeeText('Notas del equipo');
});

it('is only for client users', function () {
    ['member' => $member, 'owner' => $owner] = clientPortalSetup();

    $this->actingAs($member)->get(route('portal.index'))->assertForbidden();
    $this->actingAs($owner)->get(route('portal.index'))->assertForbidden();
});
