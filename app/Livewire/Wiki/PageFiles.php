<?php

namespace App\Livewire\Wiki;

use App\Actions\Wiki\StorePageFile;
use App\Enums\PageActivityEvent;
use App\Models\Page;
use App\Models\PageActivity;
use App\Models\PageFile;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use TallStackUi\Traits\Interactions;

/**
 * Files inside a wiki page (RN-09, P-27): there is no separate library.
 * Whoever reaches the wiki uploads and sends files to the trash.
 */
class PageFiles extends Component
{
    use Interactions, WithFileUploads;

    public const int MAX_KILOBYTES = 10240;

    #[Locked]
    public Page $page;

    /**
     * Upload and delete controls are shown only while the page is being edited.
     */
    #[Reactive]
    public bool $editing = false;

    /**
     * @var list<TemporaryUploadedFile>
     */
    public array $uploads = [];

    public function mount(): void
    {
        $this->authorize('view', $this->page);
    }

    /**
     * @return Collection<int, PageFile>
     */
    #[Computed]
    public function files(): Collection
    {
        return $this->page->files()->with('uploader:id,name')->latest()->get();
    }

    public function updatedUploads(): void
    {
        $this->authorize('update', $this->page);

        $this->validate(
            ['uploads' => ['array', 'max:10'], 'uploads.*' => ['file', 'max:'.self::MAX_KILOBYTES]],
            ['uploads.*.max' => 'Cada archivo puede pesar hasta 10 MB.'],
            ['uploads' => 'archivos', 'uploads.*' => 'archivo'],
        );

        foreach ($this->uploads as $upload) {
            app(StorePageFile::class)->handle($this->page, auth()->user(), $upload);
        }

        $count = count($this->uploads);
        $this->uploads = [];
        unset($this->files);
        $this->dispatch('page-activity-changed');
        $this->toast()->success($count === 1 ? 'Archivo subido' : "{$count} archivos subidos")->send();
    }

    public function delete(int $id): void
    {
        $this->authorize('update', $this->page);
        $file = $this->page->files()->findOrFail($id);

        DB::transaction(function () use ($file): void {
            $file->delete();
            PageActivity::record($this->page, auth()->user(), PageActivityEvent::FileDeleted, metadata: [
                'file_id' => $file->id,
                'file_name' => $file->original_name,
            ]);
        });

        unset($this->files);
        $this->dispatch('page-activity-changed');
        $this->toast()->success('Archivo enviado a la papelera')->send();
    }

    public function render(): View
    {
        return view('livewire.wiki.page-files');
    }
}
