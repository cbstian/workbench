<?php

namespace App\Actions\Trash;

use App\Enums\PageActivityEvent;
use App\Enums\TaskActivityEvent;
use App\Models\Client;
use App\Models\Page;
use App\Models\PageActivity;
use App\Models\PageFile;
use App\Models\Project;
use App\Models\ProjectPriority;
use App\Models\ProjectStatus;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\TaskLabel;
use App\Models\TrashItem;
use App\Models\WikiLabel;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Restores an element from the trash (RN-19, F-10).
 *
 * When a name is taken by an active element meanwhile, the restored one is
 * renamed with the suffix « (restaurado)» (docs/arquitectura/modelo-de-datos.md, 7).
 * A subtask or subpage whose parent is still in the trash cannot be restored.
 */
class RestoreTrashItem
{
    /**
     * Model class behind each type of the trash_items view.
     *
     * @var array<string, class-string<Model>>
     */
    public const array MODELS = [
        'workspace' => Workspace::class,
        'client' => Client::class,
        'project' => Project::class,
        'project_status' => ProjectStatus::class,
        'project_priority' => ProjectPriority::class,
        'task_label' => TaskLabel::class,
        'task' => Task::class,
        'task_comment' => TaskComment::class,
        'task_attachment' => TaskAttachment::class,
        'page' => Page::class,
        'page_file' => PageFile::class,
        'wiki_label' => WikiLabel::class,
    ];

    /**
     * Column holding the name of each type, renamed on a uniqueness conflict.
     *
     * @var array<string, string>
     */
    private const array NAME_COLUMNS = [
        'client' => 'name',
        'project_status' => 'name',
        'project_priority' => 'name',
        'task_label' => 'name',
        'wiki_label' => 'name',
    ];

    public const string RESTORED_SUFFIX = ' (restaurado)';

    public function handle(TrashItem $item): Model
    {
        /** @var Model&SoftDeletes $model */
        $model = self::MODELS[$item->type]::onlyTrashed()->findOrFail($item->id);

        $this->ensureParentIsActive($model);

        try {
            DB::transaction(fn () => $model->restore());
        } catch (QueryException $exception) {
            $column = self::NAME_COLUMNS[$item->type] ?? null;

            if ($exception->getCode() !== '23505' || $column === null) {
                throw $exception;
            }

            $model->setAttribute($column, mb_substr($model->getAttribute($column), 0, 64 - mb_strlen(self::RESTORED_SUFFIX)).self::RESTORED_SUFFIX);
            DB::transaction(fn () => $model->restore());
        }

        if ($model instanceof Task) {
            TaskActivity::record($model, Auth::user(), TaskActivityEvent::Restored);
        }

        if ($model instanceof Page) {
            PageActivity::record($model, Auth::user(), PageActivityEvent::Restored);
        }

        return $model;
    }

    private function ensureParentIsActive(Model $model): void
    {
        if (! $model instanceof Task && ! $model instanceof Page) {
            return;
        }

        $parentId = $model->getAttribute('parent_id');

        if ($parentId !== null && $model::onlyTrashed()->whereKey($parentId)->exists()) {
            throw ValidationException::withMessages([
                'item' => 'Restaura primero el elemento padre, que también está en la papelera.',
            ]);
        }
    }
}
