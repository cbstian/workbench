<?php

namespace App\Livewire\Trash;

use App\Actions\Trash\RestoreTrashItem;
use App\Livewire\Concerns\WithCurrentWorkspace;
use App\Models\TrashItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

/**
 * Trash of the current workspace (E9, F-10, RN-19): everything deleted in it,
 * most recent first, with restore. It cannot be emptied (A-19).
 *
 * An admin sees only what was deleted inside their projects; clients, wiki
 * labels and projects themselves stay with the super admin.
 */
#[Title('Papelera')]
class Index extends Component
{
    use Interactions, WithCurrentWorkspace, WithPagination;

    /**
     * Spanish names of the trash item types.
     *
     * @var array<string, string>
     */
    public const array TYPE_LABELS = [
        'client' => 'Cliente',
        'project' => 'Proyecto',
        'project_status' => 'Estado',
        'project_priority' => 'Prioridad',
        'task_label' => 'Etiqueta de tarea',
        'task' => 'Tarea',
        'task_comment' => 'Comentario de tarea',
        'task_attachment' => 'Adjunto',
        'page' => 'Página',
        'page_file' => 'Archivo',
        'page_comment' => 'Comentario de página',
        'wiki_label' => 'Etiqueta de wiki',
    ];

    /**
     * Types an admin never sees: they belong to the workspace, not to a project.
     *
     * @var list<string>
     */
    private const array OWNER_ONLY_TYPES = ['workspace', 'client', 'project', 'wiki_label'];

    #[Url(except: '')]
    public string $type = '';

    public function mount(): void
    {
        $this->authorize('viewTrash', $this->workspace);
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, TrashItem>
     */
    #[Computed]
    public function items(): LengthAwarePaginator
    {
        return $this->visibleItems()
            ->when(array_key_exists($this->type, self::TYPE_LABELS), fn ($query) => $query->where('type', $this->type))
            ->with('deleter')
            ->paginate(25);
    }

    /**
     * Project names for the items that belong to a project.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function projectNames(): array
    {
        $ids = $this->items->getCollection()->pluck('project_id')->filter()->unique()->all();

        return $this->workspace->projects()->withTrashed()->whereKey($ids)->pluck('name', 'id')->all();
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    #[Computed]
    public function typeOptions(): array
    {
        return collect(self::TYPE_LABELS)
            ->when(! $this->isOwner, fn ($labels) => $labels->except(self::OWNER_ONLY_TYPES))
            ->map(fn (string $label, string $value): array => ['label' => $label, 'value' => $value])->values()->all();
    }

    public function restore(string $type, int $id): void
    {
        $this->authorize('viewTrash', $this->workspace);

        $item = $this->visibleItems()->where('type', $type)->where('id', $id)->firstOrFail();

        $restored = app(RestoreTrashItem::class)->handle($item);

        if ($item->type === 'project') {
            $this->dispatch('projects-changed');
        }

        $renamed = str_ends_with((string) ($restored->getAttribute('name') ?? ''), RestoreTrashItem::RESTORED_SUFFIX);

        $this->toast()->success(
            self::TYPE_LABELS[$item->type].' restaurado',
            $renamed ? 'Ya existía uno con el mismo nombre, así que se renombró.' : null,
        )->send();

        unset($this->items, $this->projectNames);
    }

    #[Computed]
    public function isOwner(): bool
    {
        return auth()->user()->ownsWorkspace($this->workspace);
    }

    /**
     * Trash rows the user may see and restore.
     *
     * @return Builder<TrashItem>
     */
    private function visibleItems(): Builder
    {
        $query = TrashItem::query()->inWorkspace($this->workspace)->where('type', '!=', 'workspace');

        if (! $this->isOwner) {
            $query->whereNotIn('type', self::OWNER_ONLY_TYPES)
                ->whereIn('project_id', auth()->user()->projectIdsIn($this->workspace));
        }

        return $query;
    }

    public function render(): View
    {
        return view('livewire.trash.index');
    }
}
