<?php

use App\Models\Page;

it('shows a visible page of a shared project and of the workspace wiki (P-14)', function () {
    ['clientUser' => $clientUser, 'project' => $project, 'workspace' => $workspace] = clientPortalSetup();
    $page = Page::factory()->forProject($project)->visibleToClient()->create(['title' => 'Plan de trabajo', 'body' => '## Etapas']);
    $workspacePage = Page::factory()->for($workspace)->visibleToClient()->create(['title' => 'Cómo trabajamos']);

    $this->actingAs($clientUser)->get(route('portal.pages.show', $page))
        ->assertOk()
        ->assertSeeText('Plan de trabajo')
        ->assertSeeHtml('<h2>Etapas</h2>')
        ->assertSeeText('Conversación con el equipo');

    $this->actingAs($clientUser)->get(route('portal.pages.show', $workspacePage))->assertOk();
});

it('forbids pages not marked visible, under a hidden parent, or of a project not shared', function () {
    ['clientUser' => $clientUser, 'project' => $project, 'hiddenProject' => $hiddenProject, 'otherProject' => $otherProject] = clientPortalSetup();
    $internal = Page::factory()->forProject($project)->create();
    $underInternal = Page::factory()->childOf($internal)->visibleToClient()->create();
    $inHiddenProject = Page::factory()->forProject($hiddenProject)->visibleToClient()->create();
    $inOtherProject = Page::factory()->forProject($otherProject)->visibleToClient()->create();

    foreach ([$internal, $underInternal, $inHiddenProject, $inOtherProject] as $page) {
        $this->actingAs($clientUser)->get(route('portal.pages.show', $page))->assertForbidden();
    }
});

it('does not open the workspace wiki to a client user with no client in that workspace', function () {
    ['workspace' => $workspace, 'clientUser' => $clientUser, 'client' => $client] = clientPortalSetup();
    $page = Page::factory()->for($workspace)->visibleToClient()->create();
    $clientUser->clients()->detach($client);

    $this->actingAs($clientUser)->get(route('portal.pages.show', $page))->assertForbidden();
});
