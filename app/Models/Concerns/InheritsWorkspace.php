<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Copies `workspace_id` from the parent record when a model is created.
 *
 * Every content table carries its workspace (tenant key); the composite
 * foreign keys reject a value that does not match the parent.
 */
trait InheritsWorkspace
{
    public static function bootInheritsWorkspace(): void
    {
        static::creating(function (Model $model): void {
            if ($model->getAttribute('workspace_id') === null) {
                $model->setAttribute('workspace_id', $model->inheritedWorkspaceId());
            }
        });
    }

    /**
     * Workspace of the parent record.
     */
    abstract protected function inheritedWorkspaceId(): ?int;
}
