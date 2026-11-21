<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use App\Models\Concerns\InheritsWorkspace;
use App\Models\Concerns\TracksDeleter;
use Database\Factories\TaskAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A file stored on a task.
 *
 * The `uuid` also names the stored object; trashing the row keeps the file on disk (RN-19).
 *
 * @property int $id
 * @property string $uuid
 * @property int $workspace_id
 * @property int $task_id
 * @property int $uploaded_by_id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string|null $mime_type
 * @property int $size_bytes
 * @property string|null $checksum_sha256
 * @property Carbon|null $deleted_at
 * @property int|null $deleted_by_id
 */
#[Fillable(['uuid', 'task_id', 'uploaded_by_id', 'disk', 'path', 'original_name', 'mime_type', 'size_bytes', 'checksum_sha256'])]
class TaskAttachment extends Model
{
    /** @use HasFactory<TaskAttachmentFactory> */
    use HasFactory, HasPublicUuid, InheritsWorkspace, SoftDeletes, TracksDeleter;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    protected function inheritedWorkspaceId(): ?int
    {
        return Task::withTrashed()->whereKey($this->task_id)->value('workspace_id');
    }

    /**
     * Size for people: «850 KB», «2,4 MB».
     */
    public function humanSize(): string
    {
        return match (true) {
            $this->size_bytes >= 1048576 => number_format($this->size_bytes / 1048576, 1, ',', '.').' MB',
            $this->size_bytes >= 1024 => number_format($this->size_bytes / 1024, 0, ',', '.').' KB',
            default => $this->size_bytes.' B',
        };
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * User who uploaded the file.
     *
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }
}
