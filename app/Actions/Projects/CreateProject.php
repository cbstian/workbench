<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\ProjectPriority;
use App\Models\ProjectStatus;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

/**
 * Creates a project with the default statuses and priorities and no labels
 * (F-05, RN-06, RN-16).
 */
class CreateProject
{
    /**
     * @param  array{name: string, description?: string|null, icon?: string|null, client_id?: int|null, is_visible_to_client?: bool}  $attributes
     * @param  list<int>  $teamUserIds
     */
    public function handle(Workspace $workspace, User $creator, array $attributes, array $teamUserIds = []): Project
    {
        return DB::transaction(function () use ($workspace, $creator, $attributes, $teamUserIds): Project {
            $project = $workspace->projects()->create([
                ...$attributes,
                'created_by_id' => $creator->id,
            ]);

            $project->statuses()->createMany(ProjectStatus::DEFAULTS);
            $project->priorities()->createMany(ProjectPriority::DEFAULTS);

            if ($teamUserIds !== []) {
                $project->members()->attach(array_fill_keys($teamUserIds, ['created_by_id' => $creator->id]));
            }

            return $project;
        });
    }
}
