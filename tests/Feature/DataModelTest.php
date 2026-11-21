<?php

use App\Enums\TaskView;
use App\Enums\WorkspaceRole;
use App\Models\Client;
use App\Models\Page;
use App\Models\PageActivity;
use App\Models\PageFile;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\ProjectTaskView;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\TaskLabel;
use App\Models\User;
use App\Models\WikiLabel;
use App\Models\Workspace;
use Illuminate\Database\QueryException;

it('gives the owner and invited members access to a workspace', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $outsider = User::factory()->create();
    $workspace = Workspace::factory()->for($owner, 'owner')->create(['name' => 'Estudio']);

    $workspace->members()->attach($member, ['role' => WorkspaceRole::Member]);

    expect(Workspace::query()->visibleTo($owner)->pluck('id')->all())->toBe([$workspace->id])
        ->and(Workspace::query()->visibleTo($member)->pluck('id')->all())->toBe([$workspace->id])
        ->and(Workspace::query()->visibleTo($outsider)->pluck('id')->all())->toBe([])
        ->and($workspace->members()->whereKey($owner->id)->exists())->toBeFalse()
        ->and($workspace->members()->first()->pivot->role)->toBe(WorkspaceRole::Member);
});

it('stores a project with its client, team and default workflow', function () {
    $workspace = Workspace::factory()->create();
    $client = Client::factory()->for($workspace)->create(['name' => 'Norte Sur', 'color' => '#8f6b4a']);
    $member = User::factory()->create();

    $standalone = Project::factory()->for($workspace)->create(['name' => 'Portal interno']);
    $linked = Project::factory()->for($workspace)->for($client)->create(['name' => 'Portal de clientes']);
    $linked->members()->attach($member);

    expect($standalone->client)->toBeNull()
        ->and($linked->client->is($client))->toBeTrue()
        ->and($linked->members()->pluck('users.id')->all())->toBe([$member->id])
        ->and($linked->statuses()->orderBy('position')->pluck('name')->all())->toBe([
            'Backlog',
            'Por hacer',
            'En curso',
            'Hecho',
            'Cancelado',
        ])
        ->and($linked->initialStatus->name)->toBe('Backlog')
        ->and($linked->priorities()->orderBy('position')->pluck('name')->all())->toBe([
            'ninguna',
            'baja',
            'media',
            'alta',
            'urgente',
        ])
        ->and($linked->initialPriority->name)->toBe('ninguna');
});

it('stores a task with assignees, labels, discussion, history and one subtask', function () {
    $project = Project::factory()->create();
    $member = User::factory()->create();
    $task = Task::factory()->for($project)->create([
        'name' => 'Preparar propuesta',
        'starts_on' => '2026-05-01',
        'ends_on' => '2026-05-10',
    ]);
    $label = TaskLabel::factory()->for($project)->create(['name' => 'Diseño']);

    $task->assignees()->attach($member);
    $task->labels()->attach($label);
    TaskComment::factory()->for($task)->for($member)->create(['body' => 'Listo para revisión']);
    TaskAttachment::factory()->for($task)->for($member, 'uploader')->create(['original_name' => 'brief.pdf']);
    TaskActivity::factory()->for($task)->for($member)->create([
        'event' => 'created',
        'properties' => ['after' => ['name' => 'Preparar propuesta']],
    ]);
    $subtask = Task::factory()->subtaskOf($task)->create(['name' => 'Armar outline']);

    expect($project->rootTasks()->pluck('name')->all())->toBe(['Preparar propuesta'])
        ->and($task->assignees()->pluck('users.id')->all())->toBe([$member->id])
        ->and($task->labels()->pluck('name')->all())->toBe(['Diseño'])
        ->and($task->comments()->value('body'))->toBe('Listo para revisión')
        ->and($task->attachments()->value('original_name'))->toBe('brief.pdf')
        ->and($task->activities()->first()->properties)->toBe(['after' => ['name' => 'Preparar propuesta']])
        ->and($subtask->parent->is($task))->toBeTrue()
        ->and($task->subtasks()->pluck('name')->all())->toBe(['Armar outline']);
});

it('stores workspace and project wiki pages with files, labels and history', function () {
    $workspace = Workspace::factory()->create();
    $project = Project::factory()->for($workspace)->create();
    $author = User::factory()->create();
    $label = WikiLabel::factory()->for($workspace)->create(['name' => 'Proceso']);

    $workspacePage = Page::factory()->for($workspace)->create(['title' => 'Inicio']);
    $projectPage = Page::factory()->forProject($project)->create(['title' => 'Bitácora']);
    $child = Page::factory()->childOf($projectPage)->create(['title' => 'Decisiones']);

    $workspacePage->labels()->attach($label);
    PageFile::factory()->for($workspacePage)->for($author, 'uploader')->create(['original_name' => 'notas.md']);
    PageActivity::factory()->for($projectPage)->for($author)->create([
        'event' => 'updated',
        'properties' => ['after' => ['title' => 'Bitácora']],
    ]);

    expect($workspace->workspacePages()->pluck('title')->all())->toBe(['Inicio'])
        ->and($project->pages()->pluck('title')->all())->toContain('Bitácora', 'Decisiones')
        ->and($child->parent->is($projectPage))->toBeTrue()
        ->and($workspacePage->labels()->pluck('name')->all())->toBe(['Proceso'])
        ->and($workspacePage->files()->value('original_name'))->toBe('notas.md')
        ->and($projectPage->activities()->first()->event)->toBe('updated');
});

it('remembers one task view per user and project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $view = ProjectTaskView::factory()->for($user)->for($project)->create([
        'view' => TaskView::List,
        'filters' => [
            'status_ids' => [$project->initialStatus->id],
            'priority_ids' => [],
            'assignee_ids' => [$user->id],
            'label_ids' => [],
            'starts_from' => '2026-05-01',
            'starts_to' => null,
            'ends_from' => null,
            'ends_to' => '2026-05-31',
        ],
    ]);

    expect($view->fresh()->view)->toBe(TaskView::List)
        ->and($view->fresh()->filters['status_ids'])->toBe([$project->initialStatus->id])
        ->and($view->fresh()->filters['starts_from'])->toBe('2026-05-01');
});

it('rejects a project client from another workspace', function () {
    $workspace = Workspace::factory()->create();
    $client = Client::factory()->create();

    expect(fn () => Project::factory()->for($workspace)->for($client)->create())
        ->toThrow(function (QueryException $exception): void {
            expect($exception->getCode())->toBe('23503');
        });
});

it('rejects a task that ends before it starts', function () {
    expect(fn () => Task::factory()->create([
        'starts_on' => '2026-05-10',
        'ends_on' => '2026-05-01',
    ]))->toThrow(function (QueryException $exception): void {
        expect($exception->getCode())->toBe('23514');
    });
});

it('rejects a subtask of a subtask', function () {
    $task = Task::factory()->create();
    $subtask = Task::factory()->subtaskOf($task)->create();

    expect(fn () => Task::factory()->subtaskOf($subtask)->create())
        ->toThrow(function (QueryException $exception): void {
            expect($exception->getCode())->toBe('P0001');
        });
});

it('rejects turning a task that has subtasks into a subtask', function () {
    $project = Project::factory()->create();
    $parent = Task::factory()->for($project)->create();
    $other = Task::factory()->for($project)->create();
    Task::factory()->subtaskOf($other)->create();

    expect(function () use ($other, $parent): void {
        $other->parent_id = $parent->id;
        $other->save();
    })->toThrow(function (QueryException $exception): void {
        expect($exception->getCode())->toBe('P0001');
    });
});

it('rejects a subpage from another wiki', function () {
    $page = Page::factory()->create();
    $other = Page::factory()->create();

    expect(fn () => Page::factory()->childOf($page)->create(['workspace_id' => $other->workspace_id]))
        ->toThrow(function (QueryException $exception): void {
            expect($exception->getCode())->toBe('P0001');
        });
});

it('rejects a second initial status for a project', function () {
    $project = Project::factory()->create();

    expect(fn () => ProjectStatus::factory()->for($project)->create([
        'name' => 'En pausa',
        'is_initial' => true,
    ]))->toThrow(function (QueryException $exception): void {
        expect($exception->getCode())->toBe('23505');
    });
});

it('rejects a second saved view for the same user and project', function () {
    $view = ProjectTaskView::factory()->create();

    expect(fn () => ProjectTaskView::factory()->create([
        'user_id' => $view->user_id,
        'project_id' => $view->project_id,
    ]))->toThrow(function (QueryException $exception): void {
        expect($exception->getCode())->toBe('23505');
    });
});

it('keeps a deleted project in the trash', function () {
    $project = Project::factory()->create();

    $project->delete();

    expect(Project::query()->find($project->id))->toBeNull();

    $this->assertSoftDeleted($project);
});
