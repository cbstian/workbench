<?php

namespace App\Actions\Wiki;

use App\Enums\PageActivityEvent;
use App\Models\Page;
use App\Models\PageActivity;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Creates a page in the workspace wiki (no project) or in a project wiki,
 * optionally under a parent of the same wiki, last among its siblings (F-09).
 */
class CreatePage
{
    /**
     * @param  array{title: string, icon?: string|null, body?: string|null}  $attributes
     */
    public function handle(Workspace $workspace, ?Project $project, User $actor, array $attributes, ?Page $parent = null): Page
    {
        if ($parent !== null && ($parent->workspace_id !== $workspace->id || $parent->project_id !== $project?->id)) {
            throw new InvalidArgumentException('A subpage belongs to the same wiki as its parent.');
        }

        return DB::transaction(function () use ($workspace, $project, $actor, $attributes, $parent): Page {
            $position = (int) Page::withTrashed()
                ->inWiki($workspace, $project)
                ->where('parent_id', $parent?->id)
                ->max('position') + 1024;

            $page = Page::query()->create([
                'workspace_id' => $workspace->id,
                'project_id' => $project?->id,
                'parent_id' => $parent?->id,
                'title' => $attributes['title'],
                'icon' => $attributes['icon'] ?? null,
                'body' => $attributes['body'] ?? null,
                'position' => $position,
                'created_by_id' => $actor->id,
                'updated_by_id' => $actor->id,
            ]);

            PageActivity::record($page, $actor, PageActivityEvent::Created);

            return $page;
        });
    }
}
