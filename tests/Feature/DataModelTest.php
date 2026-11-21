<?php

use App\Enums\StatusCategory;
use App\Enums\TaskActivityEvent;
use App\Enums\TaskView;
use App\Enums\WorkspaceRole;
use App\Models\Account;
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
use App\Models\TrashItem;
use App\Models\User;
use App\Models\WikiLabel;
use App\Models\Workspace;
use Illuminate\Database\QueryException;

/**
 * Expect a closure to fail with a PostgreSQL SQLSTATE.
 */
function expectSqlState(Closure $action, string $sqlState): void
{
    expect($action)->toThrow(function (QueryException $exception) use ($sqlState): void {
        expect($exception->getCode())->toBe($sqlState);
    });
}

it('makes the owner of an account its super admin', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $account = Account::openFor($owner);

    expect($owner->fresh()->account_id)->toBe($account->id)
        ->and($owner->isSuperAdmin())->toBeTrue()
        ->and($member->isSuperAdmin())->toBeFalse();
});

it('gives the account owner and invited members access to a workspace', function () {
    $workspace = Workspace::factory()->create(['name' => 'Estudio']);
    $owner = $workspace->account->owner;
    $member = User::factory()->memberOf($workspace)->create();
    $outsider = User::factory()->create();

    expect(Workspace::query()->visibleTo($owner)->pluck('id')->all())->toBe([$workspace->id])
        ->and(Workspace::query()->visibleTo($member)->pluck('id')->all())->toBe([$workspace->id])
        ->and(Workspace::query()->visibleTo($outsider)->pluck('id')->all())->toBe([])
        ->and($owner->ownedWorkspaces()->pluck('workspaces.id')->all())->toBe([$workspace->id])
        ->and($workspace->members()->whereKey($owner->id)->exists())->toBeFalse()
        ->and($workspace->members()->first()->pivot->role)->toBe(WorkspaceRole::Member);
});

it('stores a project with its client, team and default workflow', function () {
    $workspace = Workspace::factory()->create();
    $client = Client::factory()->for($workspace)->create(['name' => 'Norte Sur', 'color' => '#8f6b4a']);
    $member = User::factory()->memberOf($workspace)->create();

    $standalone = Project::factory()->for($workspace)->create(['name' => 'Portal interno']);
    $linked = Project::factory()->for($workspace)->for($client)->create(['name' => 'Portal de clientes']);
    $linked->members()->attach($member);

    expect($standalone->client)->toBeNull()
        ->and($linked->client->is($client))->toBeTrue()
        ->and($linked->members()->pluck('users.id')->all())->toBe([$member->id])
        ->and($linked->statuses()->pluck('category', 'name')->all())->toBe([
            'Backlog' => StatusCategory::Backlog,
            'Por hacer' => StatusCategory::Unstarted,
            'En curso' => StatusCategory::Started,
            'Hecho' => StatusCategory::Completed,
            'Cancelado' => StatusCategory::Cancelled,
        ])
        ->and($linked->initialStatus->name)->toBe('Backlog')
        ->and($linked->priorities()->pluck('name')->all())->toBe(['ninguna', 'baja', 'media', 'alta', 'urgente'])
        ->and($linked->initialPriority->name)->toBe('ninguna')
        ->and($linked->uuid)->toBeUuid()
        ->and($linked->getRouteKey())->toBe($linked->uuid);
});

it('stores a task with assignees, labels, discussion, history and one subtask', function () {
    $project = Project::factory()->create();
    $member = User::factory()->memberOf($project->workspace)->create();
    $project->members()->attach($member);
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
        'event' => TaskActivityEvent::Created,
        'changes' => ['name' => ['old' => null, 'new' => 'Preparar propuesta']],
    ]);
    $subtask = Task::factory()->subtaskOf($task)->create(['name' => 'Armar outline']);

    expect($task->workspace_id)->toBe($project->workspace_id)
        ->and($project->rootTasks()->pluck('name')->all())->toBe(['Preparar propuesta'])
        ->and($task->assignees()->pluck('users.id')->all())->toBe([$member->id])
        ->and($task->labels()->pluck('name')->all())->toBe(['Diseño'])
        ->and($task->comments()->value('body'))->toBe('Listo para revisión')
        ->and($task->attachments()->first()->workspace_id)->toBe($project->workspace_id)
        ->and($task->activities()->first()->event)->toBe(TaskActivityEvent::Created)
        ->and($task->activities()->first()->changes)->toEqual(['name' => ['old' => null, 'new' => 'Preparar propuesta']])
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
    PageActivity::factory()->for($projectPage)->for($author)->create();

    expect($workspace->workspacePages()->pluck('title')->all())->toBe(['Inicio'])
        ->and($project->pages()->pluck('title')->all())->toContain('Bitácora', 'Decisiones')
        ->and($child->parent->is($projectPage))->toBeTrue()
        ->and($workspacePage->labels()->pluck('name')->all())->toBe(['Proceso'])
        ->and($workspacePage->files()->value('original_name'))->toBe('notas.md')
        ->and($projectPage->activities()->first()->workspace_id)->toBe($workspace->id);
});

it('remembers one task view per user and project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $view = ProjectTaskView::factory()->for($user)->for($project)->create([
        'view' => TaskView::List,
        'filters' => [
            'v' => 1,
            'status_ids' => [$project->initialStatus->id],
            'date_range' => ['from' => '2026-05-01', 'to' => '2026-05-31'],
        ],
    ]);

    expect($view->fresh()->view)->toBe(TaskView::List)
        ->and($view->fresh()->filters['status_ids'])->toBe([$project->initialStatus->id])
        ->and($view->fresh()->filters['date_range'])->toEqual(['from' => '2026-05-01', 'to' => '2026-05-31']);
});

it('rejects a project client from another workspace', function () {
    $workspace = Workspace::factory()->create();
    $client = Client::factory()->create();

    expectSqlState(fn () => Project::factory()->for($workspace)->for($client)->create(), '23503');
});

it('rejects a client user on a project team', function () {
    $project = Project::factory()->create();
    $clientUser = User::factory()->memberOf($project->workspace, WorkspaceRole::Client)->create();

    expectSqlState(fn () => $project->members()->attach($clientUser), '23514');
});

it('rejects a project team member who is not in the workspace', function () {
    $project = Project::factory()->create();
    $outsider = User::factory()->create();

    expectSqlState(fn () => $project->members()->attach($outsider), '23514');
});

it('rejects assigning a task to a user outside the project team', function () {
    $task = Task::factory()->create();
    $member = User::factory()->memberOf($task->project->workspace)->create();

    expectSqlState(fn () => $task->assignees()->attach($member), '23503');
});

it('rejects linking a non client user to a client', function () {
    $client = Client::factory()->create();
    $member = User::factory()->memberOf($client->workspace)->create();

    expectSqlState(fn () => $client->users()->attach($member), '23514');
});

it('rejects turning a project team member into a client user', function () {
    $project = Project::factory()->create();
    $member = User::factory()->memberOf($project->workspace)->create();
    $project->members()->attach($member);

    expectSqlState(
        fn () => $project->workspace->members()->updateExistingPivot($member->id, ['role' => WorkspaceRole::Client]),
        '23514',
    );
});

it('rejects a task status from another project', function () {
    $task = Task::factory()->create();
    $foreignStatus = Project::factory()->create()->initialStatus;

    expectSqlState(fn () => $task->update(['status_id' => $foreignStatus->id]), '23503');
});

it('rejects a task that uses a trashed status', function () {
    $project = Project::factory()->create();
    $status = ProjectStatus::factory()->for($project)->create();
    $status->delete();

    expectSqlState(fn () => Task::factory()->for($project)->create(['status_id' => $status->id]), '23514');
});

it('rejects trashing the initial status of a project', function () {
    $status = Project::factory()->create()->initialStatus;

    expectSqlState(fn () => $status->delete(), '23514');
});

it('rejects a second initial status for a project', function () {
    $project = Project::factory()->create();

    expectSqlState(fn () => ProjectStatus::factory()->for($project)->create(['is_initial' => true]), '23505');
});

it('stamps a task as completed only while its status is closed', function () {
    $task = Task::factory()->create();
    $done = $task->project->statuses()->where('category', StatusCategory::Completed)->first();
    $started = $task->project->statuses()->where('category', StatusCategory::Started)->first();

    $task->update(['status_id' => $done->id]);
    $completedAt = $task->fresh()->completed_at;
    $task->update(['status_id' => $started->id]);

    expect($completedAt)->not->toBeNull()
        ->and($task->fresh()->completed_at)->toBeNull();
});

it('rejects a task that ends before it starts', function () {
    expectSqlState(fn () => Task::factory()->create([
        'starts_on' => '2026-05-10',
        'ends_on' => '2026-05-01',
    ]), '23514');
});

it('rejects a subtask of a subtask', function () {
    $task = Task::factory()->create();
    $subtask = Task::factory()->subtaskOf($task)->create();

    expectSqlState(fn () => Task::factory()->subtaskOf($subtask)->create(), '23514');
});

it('rejects turning a task that has subtasks into a subtask', function () {
    $project = Project::factory()->create();
    $parent = Task::factory()->for($project)->create();
    $other = Task::factory()->for($project)->create();
    Task::factory()->subtaskOf($other)->create();

    expectSqlState(fn () => $other->update(['parent_id' => $parent->id]), '23514');
});

it('rejects a subpage from another wiki', function () {
    $page = Page::factory()->create();
    $other = Page::factory()->create();

    expectSqlState(fn () => Page::factory()->childOf($page)->create(['workspace_id' => $other->workspace_id]), '23514');
});

it('rejects moving a page under one of its subpages', function () {
    $page = Page::factory()->create();
    $child = Page::factory()->childOf($page)->create();

    expectSqlState(fn () => $page->update(['parent_id' => $child->id]), '23514');
});

it('keeps task history append-only', function () {
    $activity = TaskActivity::factory()->create();

    expectSqlState(fn () => $activity->delete(), '23514');
});

it('rejects a second saved view for the same user and project', function () {
    $view = ProjectTaskView::factory()->create();

    expectSqlState(fn () => ProjectTaskView::factory()->create([
        'user_id' => $view->user_id,
        'project_id' => $view->project_id,
    ]), '23505');
});

it('lists a deleted project in the trash with who deleted it', function () {
    $project = Project::factory()->create();
    $owner = $project->workspace->account->owner;
    $this->actingAs($owner);

    $project->delete();

    $this->assertSoftDeleted($project);
    expect(TrashItem::query()->inWorkspace($project->workspace)->get(['type', 'id', 'deleted_by_id'])->toArray())
        ->toBe([['type' => 'project', 'id' => $project->id, 'deleted_by_id' => $owner->id]]);
});

it('clears who deleted a model when it is restored', function () {
    $project = Project::factory()->create();
    $this->actingAs($project->workspace->account->owner);
    $project->delete();

    $project->restore();

    expect($project->fresh()->deleted_by_id)->toBeNull();
});
