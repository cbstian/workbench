<?php

namespace App\Models;

use App\Enums\TaskActivityEvent;
use App\Models\Concerns\InheritsWorkspace;
use Database\Factories\TaskActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One recorded change of a task. A history entry, not a content version (RN-08).
 *
 * Append-only: the database rejects updates and deletes. `changes` holds
 * `{"field": {"old": ..., "new": ...}}` for the fields that changed.
 *
 * @property int $id
 * @property int $workspace_id
 * @property int $task_id
 * @property int|null $user_id
 * @property TaskActivityEvent $event
 * @property array<string, array{old: mixed, new: mixed}>|null $changes
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 */
#[Fillable(['task_id', 'user_id', 'event', 'changes', 'metadata'])]
class TaskActivity extends Model
{
    /** @use HasFactory<TaskActivityFactory> */
    use HasFactory, InheritsWorkspace;

    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event' => TaskActivityEvent::class,
            'changes' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Append an entry to the history of a task.
     *
     * @param  array<string, array{old: mixed, new: mixed}>|null  $changes
     * @param  array<string, mixed>|null  $metadata
     */
    public static function record(Task $task, ?User $user, TaskActivityEvent $event, ?array $changes = null, ?array $metadata = null): self
    {
        return self::query()->create([
            'task_id' => $task->id,
            'user_id' => $user?->id,
            'event' => $event,
            'changes' => $changes,
            'metadata' => $metadata,
        ]);
    }

    /**
     * The change in Spanish, for the task history (without the author).
     */
    public function description(): string
    {
        $changes = $this->getAttribute('changes') ?? [];
        $metadata = $this->metadata ?? [];
        $name = fn (string $field, string $side): string => (string) data_get($changes, "{$field}.{$side}.name", data_get($changes, "{$field}.{$side}", '—'));

        return match ($this->event) {
            TaskActivityEvent::Created => 'creó la tarea',
            TaskActivityEvent::Updated => 'cambió '.collect(array_keys($changes))
                ->map(fn (string $field): string => match ($field) {
                    'name' => 'el nombre',
                    'description' => 'la descripción',
                    'starts_on' => 'la fecha de inicio',
                    'ends_on' => 'la fecha de término',
                    default => $field,
                })->join(', ', ' y '),
            TaskActivityEvent::StatusChanged => "cambió el estado de «{$name('status', 'old')}» a «{$name('status', 'new')}»"
                .(($metadata['reason'] ?? null) === 'status_deleted' ? ' (se eliminó el estado)' : ''),
            TaskActivityEvent::PriorityChanged => "cambió la prioridad de «{$name('priority', 'old')}» a «{$name('priority', 'new')}»"
                .(($metadata['reason'] ?? null) === 'priority_deleted' ? ' (se eliminó la prioridad)' : ''),
            TaskActivityEvent::AssigneeAdded => 'asignó a '.($metadata['user_name'] ?? 'un usuario'),
            TaskActivityEvent::AssigneeRemoved => 'quitó a '.($metadata['user_name'] ?? 'un usuario'),
            TaskActivityEvent::LabelAdded => 'agregó la etiqueta «'.($metadata['label_name'] ?? '—').'»',
            TaskActivityEvent::LabelRemoved => 'quitó la etiqueta «'.($metadata['label_name'] ?? '—').'»',
            TaskActivityEvent::SubtaskAdded => 'agregó la subtarea «'.($metadata['subtask_name'] ?? '—').'»',
            TaskActivityEvent::SubtaskRemoved => 'eliminó la subtarea «'.($metadata['subtask_name'] ?? '—').'»',
            TaskActivityEvent::Moved => 'movió la tarea',
            TaskActivityEvent::CommentAdded => 'comentó',
            TaskActivityEvent::CommentDeleted => 'eliminó un comentario',
            TaskActivityEvent::AttachmentAdded => 'adjuntó «'.($metadata['file_name'] ?? 'un archivo').'»',
            TaskActivityEvent::AttachmentDeleted => 'eliminó el adjunto «'.($metadata['file_name'] ?? '—').'»',
            TaskActivityEvent::Deleted => 'envió la tarea a la papelera',
            TaskActivityEvent::Restored => 'restauró la tarea',
        };
    }

    protected function inheritedWorkspaceId(): ?int
    {
        return Task::withTrashed()->whereKey($this->task_id)->value('workspace_id');
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * User who made the change; null for console actions.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
