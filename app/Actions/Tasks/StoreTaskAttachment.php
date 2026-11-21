<?php

namespace App\Actions\Tasks;

use App\Actions\Concerns\StoresPrivateFile;
use App\Enums\TaskActivityEvent;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Stores a file on a task (F-07) on the private disk, at
 * workspaces/{workspace_uuid}/tasks/{task_uuid}/{uuid}, and records it in the
 * task history. The original name is kept only in the database.
 */
class StoreTaskAttachment
{
    use StoresPrivateFile;

    public function handle(Task $task, User $actor, UploadedFile $file): TaskAttachment
    {
        $stored = $this->storePrivately($file, "workspaces/{$task->project->workspace->uuid}/tasks/{$task->uuid}");

        return DB::transaction(function () use ($task, $actor, $stored): TaskAttachment {
            $attachment = TaskAttachment::query()->create([
                ...$stored,
                'task_id' => $task->id,
                'uploaded_by_id' => $actor->id,
            ]);

            TaskActivity::record($task, $actor, TaskActivityEvent::AttachmentAdded, metadata: [
                'attachment_id' => $attachment->id,
                'file_name' => $attachment->original_name,
            ]);

            return $attachment;
        });
    }
}
