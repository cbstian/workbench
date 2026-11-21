<?php

namespace App\Livewire\Tasks;

use App\Actions\Tasks\StoreTaskAttachment;
use App\Enums\TaskActivityEvent;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskAttachment;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use TallStackUi\Traits\Interactions;

/**
 * Files of a task (F-07). Whoever may act on the task uploads; the uploader
 * or an admin sends one to the trash, where the file stays on disk (RN-19).
 */
class Attachments extends Component
{
    use Interactions, WithFileUploads;

    public const int MAX_KILOBYTES = 10240;

    #[Locked]
    public Task $task;

    /**
     * @var list<TemporaryUploadedFile>
     */
    public array $uploads = [];

    public function mount(): void
    {
        $this->authorize('view', $this->task);
    }

    /**
     * @return Collection<int, TaskAttachment>
     */
    #[Computed]
    public function attachments(): Collection
    {
        return $this->task->attachments()->with('uploader:id,name')->latest()->get();
    }

    #[Computed]
    public function canAttach(): bool
    {
        return auth()->user()->can('collaborate', $this->task);
    }

    public function canDelete(TaskAttachment $attachment): bool
    {
        return ($attachment->uploaded_by_id === auth()->id() && $this->canAttach)
            || auth()->user()->can('moderate', $this->task);
    }

    /**
     * Store the files as soon as they finish uploading.
     */
    public function updatedUploads(): void
    {
        $this->authorize('collaborate', $this->task);

        $this->validate(
            ['uploads' => ['array', 'max:10'], 'uploads.*' => ['file', 'max:'.self::MAX_KILOBYTES]],
            ['uploads.*.max' => 'Cada archivo puede pesar hasta 10 MB.'],
            ['uploads' => 'archivos', 'uploads.*' => 'archivo'],
        );

        foreach ($this->uploads as $upload) {
            app(StoreTaskAttachment::class)->handle($this->task, auth()->user(), $upload);
        }

        $count = count($this->uploads);
        $this->uploads = [];
        unset($this->attachments);
        $this->dispatch('task-activity-changed');
        $this->toast()->success($count === 1 ? 'Archivo adjuntado' : "{$count} archivos adjuntados")->send();
    }

    public function delete(int $id): void
    {
        $attachment = $this->task->attachments()->findOrFail($id);
        abort_unless($this->canDelete($attachment), 403);

        DB::transaction(function () use ($attachment): void {
            $attachment->delete();
            TaskActivity::record($this->task, auth()->user(), TaskActivityEvent::AttachmentDeleted, metadata: [
                'attachment_id' => $attachment->id,
                'file_name' => $attachment->original_name,
            ]);
        });

        unset($this->attachments);
        $this->dispatch('task-activity-changed');
        $this->toast()->success('Adjunto enviado a la papelera')->send();
    }

    public function render(): View
    {
        return view('livewire.tasks.attachments');
    }
}
