<?php

use App\Enums\PageActivityEvent;
use App\Enums\WorkspaceRole;
use App\Livewire\Trash\Index as TrashIndex;
use App\Livewire\Wiki\Index;
use App\Livewire\Wiki\PageFiles;
use App\Livewire\Wiki\PageShow;
use App\Models\Page;
use App\Models\Project;
use App\Models\User;
use App\Models\WikiLabel;
use App\Models\Workspace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * A workspace with a project whose team has an admin and a member.
 *
 * @return array{owner: User, workspace: Workspace, project: Project, admin: User, member: User}
 */
function wikiSetup(): array
{
    [$owner, $workspace] = superAdminWithWorkspace();
    $project = Project::factory()->for($workspace)->create();
    $admin = User::factory()->memberOf($workspace, WorkspaceRole::Admin)->create();
    $member = User::factory()->memberOf($workspace)->create();
    $project->members()->attach([$admin->id, $member->id]);

    return compact('owner', 'workspace', 'project', 'admin', 'member');
}

it('lets only the super admin into the workspace wiki (RN-24, A-18)', function () {
    ['owner' => $owner, 'workspace' => $workspace, 'admin' => $admin, 'member' => $member] = wikiSetup();
    $page = Page::factory()->create(['workspace_id' => $workspace->id, 'title' => 'Manual interno']);

    $this->actingAs($owner)->get(route('wiki.index'))->assertOk()->assertSeeText('Manual interno');
    $this->actingAs($owner)->get(route('pages.show', $page))->assertOk();

    foreach ([$admin, $member] as $user) {
        $this->actingAs($user)->get(route('wiki.index'))->assertForbidden();
        $this->actingAs($user)->get(route('pages.show', $page))->assertForbidden();
    }
});

it('lets the project team into the project wiki, nobody else (RN-24)', function () {
    ['project' => $project, 'admin' => $admin, 'member' => $member, 'workspace' => $workspace] = wikiSetup();
    $page = Page::factory()->forProject($project)->create(['title' => 'Arquitectura']);
    $outsider = User::factory()->memberOf($workspace, WorkspaceRole::Admin)->create();
    $clientUser = User::factory()->memberOf($workspace, WorkspaceRole::Client)->create();

    $this->actingAs($member)->get(route('projects.wiki', $project))->assertOk()->assertSeeText('Arquitectura');
    $this->actingAs($admin)->get(route('pages.show', $page))->assertOk();

    foreach ([$outsider, $clientUser] as $user) {
        $this->actingAs($user)->get(route('projects.wiki', $project))->assertForbidden();
        $this->actingAs($user)->get(route('pages.show', $page))->assertForbidden();
    }
});

it('creates pages and subpages in the right wiki (F-09)', function () {
    ['project' => $project, 'member' => $member] = wikiSetup();

    Livewire::actingAs($member)->test(Index::class, ['project' => $project])
        ->call('create')
        ->set('title', 'Guía de despliegue')
        ->call('save')
        ->assertHasNoErrors();

    $page = $project->pages()->sole();

    expect($page->title)->toBe('Guía de despliegue')
        ->and($page->workspace_id)->toBe($project->workspace_id)
        ->and($page->activities()->pluck('event')->all())->toBe([PageActivityEvent::Created]);

    Livewire::actingAs($member)->test(PageShow::class, ['page' => $page])
        ->set('newSubpageTitle', 'Servidores')
        ->call('addSubpage')
        ->assertHasNoErrors();

    expect($page->children()->sole()->title)->toBe('Servidores');
});

it('edits a page and records the history without versions (RN-09)', function () {
    ['owner' => $owner, 'workspace' => $workspace] = wikiSetup();
    $page = Page::factory()->create(['workspace_id' => $workspace->id, 'title' => 'Antes', 'body' => 'Hola']);

    Livewire::actingAs($owner)->test(PageShow::class, ['page' => $page])
        ->call('startEditing')
        ->set('title', 'Después')
        ->set('body', "# Título\n\nTexto **importante**.")
        ->set('isVisibleToClient', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('editing', false)
        ->assertSeeHtml('<strong>importante</strong>')
        ->assertSeeText('editó el contenido');

    $page->refresh();

    expect($page->title)->toBe('Después')
        ->and($page->is_visible_to_client)->toBeTrue()
        ->and($page->activities()->pluck('event')->map->value->sort()->values()->all())->toBe(['body_updated', 'updated'])
        ->and($page->activities()->where('event', 'body_updated')->sole()->metadata)->toEqual(['old_length' => 4, 'new_length' => 31]);
});

it('escapes raw HTML in the body', function () {
    ['owner' => $owner, 'workspace' => $workspace] = wikiSetup();
    $page = Page::factory()->create(['workspace_id' => $workspace->id, 'body' => "<script>alert(1)</script>\n\n[x](javascript:alert(1))"]);

    expect($page->bodyHtml())->not->toContain('<script>')->not->toContain('javascript:');
});

it('moves a page but never under itself or its subpages', function () {
    ['owner' => $owner, 'workspace' => $workspace] = wikiSetup();
    $parent = Page::factory()->create(['workspace_id' => $workspace->id, 'title' => 'Padre']);
    $child = Page::factory()->create(['workspace_id' => $workspace->id, 'parent_id' => $parent->id, 'title' => 'Hija']);
    $other = Page::factory()->create(['workspace_id' => $workspace->id, 'title' => 'Otra']);

    Livewire::actingAs($owner)->test(PageShow::class, ['page' => $parent])
        ->call('startEditing')
        ->set('parentId', $child->id)
        ->call('save')
        ->assertHasErrors(['parentId']);

    Livewire::actingAs($owner)->test(PageShow::class, ['page' => $parent])
        ->call('startEditing')
        ->set('parentId', $other->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($parent->fresh()->parent_id)->toBe($other->id)
        ->and($parent->activities()->where('event', 'moved')->sole()->description())->toBe('movió la página a «Otra»');
});

it('labels pages with workspace wiki labels and finds them by label (F-09)', function () {
    ['project' => $project, 'admin' => $admin] = wikiSetup();
    $tagged = Page::factory()->forProject($project)->create(['title' => 'Con etiqueta']);
    Page::factory()->forProject($project)->create(['title' => 'Sin etiqueta']);

    Livewire::actingAs($admin)->test(PageShow::class, ['page' => $tagged])
        ->call('startEditing')
        ->set('newLabel', 'Procesos')
        ->call('addLabel')
        ->call('save')
        ->assertHasNoErrors();

    $label = $project->workspace->wikiLabels()->sole();

    expect($label->name)->toBe('Procesos')
        ->and($tagged->labels()->pluck('wiki_labels.id')->all())->toBe([$label->id]);

    Livewire::actingAs($admin)->test(Index::class, ['project' => $project])
        ->call('filterByLabel', $label->id)
        ->assertSeeText('Resultados')
        ->assertSeeTextInOrder(['Resultados', 'Con etiqueta'])
        ->assertDontSeeText('No encontramos');
});

it('gives new wiki labels a color and toggles existing ones on the page', function () {
    ['project' => $project, 'admin' => $admin] = wikiSetup();
    $page = Page::factory()->forProject($project)->create();
    $existing = WikiLabel::factory()->for($project->workspace)->create(['name' => 'Guía']);

    $component = Livewire::actingAs($admin)->test(PageShow::class, ['page' => $page])
        ->call('startEditing')
        ->set('newLabel', 'Procesos')
        ->call('addLabel')
        ->call('toggleLabel', $existing->id);

    $created = $project->workspace->wikiLabels()->where('name', 'Procesos')->sole();

    expect($created->color)->toBeIn(WikiLabel::COLORS)
        ->and($component->get('labelIds'))->toBe([$created->id, $existing->id]);

    $component->call('toggleLabel', $existing->id)
        ->call('toggleLabel', WikiLabel::factory()->create()->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($page->labels()->pluck('wiki_labels.id')->all())->toBe([$created->id]);
});

it('shows subpage and file controls only while editing the page', function () {
    ['project' => $project, 'admin' => $admin] = wikiSetup();
    $page = Page::factory()->forProject($project)->create();

    Livewire::actingAs($admin)->test(PageShow::class, ['page' => $page])
        ->assertDontSee('Nueva subpágina…')
        ->call('startEditing')
        ->assertSee('Nueva subpágina…');

    Livewire::actingAs($admin)->test(PageFiles::class, ['page' => $page])
        ->assertDontSee('Subir archivos a esta página…');

    Livewire::actingAs($admin)->test(PageFiles::class, ['page' => $page, 'editing' => true])
        ->assertSee('Subir archivos a esta página…');
});

it('searches pages by text', function () {
    ['project' => $project, 'member' => $member] = wikiSetup();
    Page::factory()->forProject($project)->create(['title' => 'Despliegue', 'body' => 'Usamos contenedores en producción.']);
    Page::factory()->forProject($project)->create(['title' => 'Otra cosa', 'body' => 'Nada que ver.']);

    $results = Livewire::actingAs($member)->test(Index::class, ['project' => $project])
        ->set('search', 'produccion')
        ->instance()->results;

    expect($results->pluck('title')->all())->toBe(['Despliegue']);
});

it('uploads files inside a page and downloads them only with access', function () {
    Storage::fake('local');
    ['project' => $project, 'member' => $member, 'workspace' => $workspace] = wikiSetup();
    $page = Page::factory()->forProject($project)->create();
    $outsider = User::factory()->memberOf($workspace)->create();

    Livewire::actingAs($member)->test(PageFiles::class, ['page' => $page])
        ->set('uploads', [UploadedFile::fake()->createWithContent('acta.txt', 'contenido')])
        ->assertHasNoErrors()
        ->assertSeeText('acta.txt');

    $file = $page->files()->sole();

    expect($file->path)->toBe("workspaces/{$workspace->uuid}/pages/{$page->uuid}/{$file->uuid}")
        ->and($page->activities()->where('event', 'file_added')->sole()->description())->toBe('subió «acta.txt»');

    $this->actingAs($member)->get(route('page-files.show', $file))->assertOk()->assertDownload('acta.txt');
    $this->actingAs($outsider)->get(route('page-files.show', $file))->assertForbidden();
});

it('sends a page to the trash hiding its subpages, and restores it', function () {
    ['owner' => $owner, 'project' => $project] = wikiSetup();
    $page = Page::factory()->forProject($project)->create(['title' => 'Raíz']);
    $child = Page::factory()->forProject($project)->create(['parent_id' => $page->id, 'title' => 'Hija']);

    Livewire::actingAs($owner)->test(PageShow::class, ['page' => $page])
        ->call('delete')
        ->assertRedirect(route('projects.wiki', $project));

    expect($page->fresh()->trashed())->toBeTrue()
        ->and($child->fresh()->trashed())->toBeFalse();

    $this->actingAs($owner)->get(route('pages.show', $child))->assertForbidden();

    Livewire::actingAs($owner)->test(TrashIndex::class)->call('restore', 'page', $page->id);

    expect($page->fresh()->trashed())->toBeFalse()
        ->and($page->activities()->pluck('event')->map->value->all())->toContain('deleted', 'restored');
    $this->actingAs($owner)->get(route('pages.show', $child))->assertOk();
});

it('shows the wiki links to whoever may open them', function () {
    ['owner' => $owner, 'project' => $project, 'member' => $member] = wikiSetup();

    $this->actingAs($owner)->get(route('dashboard'))->assertSee(route('wiki.index'), escape: false);
    $this->actingAs($member)->get(route('dashboard'))->assertDontSee(route('wiki.index'), escape: false);
    $this->actingAs($member)->get(route('projects.show', $project))->assertSee(route('projects.wiki', $project), escape: false);
});
