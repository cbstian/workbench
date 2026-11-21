<?php

namespace App\Livewire\Wiki;

use App\Actions\Wiki\CreatePage;
use App\Actions\Wiki\UpdatePage;
use App\Enums\PageActivityEvent;
use App\Livewire\Concerns\WithWikiTree;
use App\Models\Page;
use App\Models\PageActivity;
use App\Models\Project;
use App\Models\WikiLabel;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

/**
 * A wiki page (F-09): read it, edit it (title, icon, Markdown body, labels,
 * parent and client visibility), add subpages and files, and see its history.
 * Only the history of changes is kept, no versions (RN-09, P-15).
 */
class PageShow extends Component
{
    use Interactions, WithWikiTree;

    public Page $page;

    #[Url(as: 'edit', except: false)]
    public bool $editing = false;

    public string $title = '';

    public ?string $icon = null;

    public ?string $body = null;

    public bool $isVisibleToClient = false;

    public ?int $parentId = null;

    /**
     * @var list<int>
     */
    public array $labelIds = [];

    public string $newLabel = '';

    public string $newSubpageTitle = '';

    public function mount(): void
    {
        $this->authorize('view', $this->page);

        if (app(CurrentWorkspace::class)->get()?->id !== $this->page->workspace_id) {
            app(CurrentWorkspace::class)->switchTo($this->page->workspace);
        }

        $this->fillForm();
        $this->editing = $this->editing && auth()->user()->can('update', $this->page);
    }

    protected function wikiWorkspace(): Workspace
    {
        return $this->page->workspace;
    }

    protected function wikiProject(): ?Project
    {
        return $this->page->project;
    }

    /**
     * @return Collection<int, Page>
     */
    #[Computed]
    public function subpages(): Collection
    {
        return $this->page->children()->get(['id', 'uuid', 'title', 'icon', 'updated_at']);
    }

    /**
     * @return Collection<int, PageActivity>
     */
    #[Computed]
    public function activities(): Collection
    {
        return $this->page->activities()->with('user:id,name')->latest('created_at')->latest('id')->limit(100)->get();
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    #[Computed]
    public function labelOptions(): array
    {
        return $this->page->workspace->wikiLabels()->orderBy('name')->get(['id', 'name'])
            ->map(fn (WikiLabel $label): array => ['label' => $label->name, 'value' => $label->id])
            ->all();
    }

    /**
     * Ancestors from the root down to the parent, for the breadcrumbs.
     *
     * @return list<Page>
     */
    #[Computed]
    public function ancestors(): array
    {
        $ancestors = [];
        $current = $this->page->parent;

        while ($current !== null) {
            array_unshift($ancestors, $current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    public function startEditing(): void
    {
        $this->authorize('update', $this->page);

        $this->fillForm();
        $this->editing = true;
    }

    public function cancelEditing(): void
    {
        $this->fillForm();
        $this->resetValidation();
        $this->editing = false;
    }

    public function save(): void
    {
        $this->authorize('update', $this->page);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:32'],
            'body' => ['nullable', 'string', 'max:200000'],
            'isVisibleToClient' => ['boolean'],
            'parentId' => ['nullable', 'integer', function (string $attribute, mixed $value, Closure $fail): void {
                if (! in_array((int) $value, array_column($this->parentOptions($this->page->id), 'value'), true)) {
                    $fail('Elige una página de esta wiki que no sea esta ni una de sus subpáginas.');
                }
            }],
            'labelIds' => ['array'],
            'labelIds.*' => ['integer', 'in:'.implode(',', array_column($this->labelOptions, 'value'))],
        ], attributes: [
            'title' => 'título', 'icon' => 'icono', 'body' => 'contenido',
            'parentId' => 'página padre', 'labelIds' => 'etiquetas', 'labelIds.*' => 'etiqueta',
        ]);

        app(UpdatePage::class)->handle($this->page, auth()->user(), [
            'title' => trim($validated['title']),
            'icon' => $validated['icon'] ?: null,
            'body' => filled($validated['body']) ? $validated['body'] : null,
            'is_visible_to_client' => (bool) $validated['isVisibleToClient'],
            'parent_id' => $validated['parentId'] ? (int) $validated['parentId'] : null,
        ], array_values(array_unique(array_map('intval', $validated['labelIds']))));

        $this->page->refresh();
        $this->editing = false;
        $this->dispatch('wiki-changed');
        unset($this->activities, $this->ancestors, $this->tree);
        $this->toast()->success('Página guardada')->send();
    }

    /**
     * Create a wiki label of the workspace and select it.
     */
    public function addLabel(): void
    {
        $this->authorize('update', $this->page);

        $validated = $this->validate(['newLabel' => ['required', 'string', 'max:64']], attributes: ['newLabel' => 'etiqueta']);
        $name = trim($validated['newLabel']);

        $label = $this->page->workspace->wikiLabels()->whereRaw('lower(name) = ?', [mb_strtolower($name)])->first()
            ?? $this->page->workspace->wikiLabels()->create(['name' => $name]);

        $this->labelIds = array_values(array_unique([...$this->labelIds, $label->id]));
        $this->newLabel = '';
        unset($this->labelOptions);
    }

    public function addSubpage(): void
    {
        $this->authorize('update', $this->page);

        $validated = $this->validate(['newSubpageTitle' => ['required', 'string', 'max:255']], attributes: ['newSubpageTitle' => 'título de la subpágina']);

        $subpage = app(CreatePage::class)->handle($this->page->workspace, $this->page->project, auth()->user(), ['title' => trim($validated['newSubpageTitle'])], $this->page);

        $this->dispatch('wiki-changed');
        $this->redirectRoute('pages.show', ['page' => $subpage, 'edit' => 1], navigate: true);
    }

    public function confirmDelete(): void
    {
        $this->dialog()
            ->question("¿Enviar «{$this->page->title}» a la papelera?", 'Sus subpáginas se ocultan con ella. Puedes restaurarla desde la papelera.')
            ->confirm('Enviar a la papelera', 'delete')
            ->cancel('Cancelar')
            ->send();
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->page);

        DB::transaction(function (): void {
            PageActivity::record($this->page, auth()->user(), PageActivityEvent::Deleted);
            $this->page->delete();
        });

        $this->toast()->success('Página enviada a la papelera')->flash()->send();

        $this->page->parent !== null
            ? $this->redirectRoute('pages.show', $this->page->parent, navigate: true)
            : $this->redirectRoute($this->page->project_id ? 'projects.wiki' : 'wiki.index', $this->page->project_id ? $this->page->project : [], navigate: true);
    }

    #[On('page-activity-changed')]
    public function refreshActivities(): void
    {
        unset($this->activities);
    }

    private function fillForm(): void
    {
        $this->title = $this->page->title;
        $this->icon = $this->page->icon;
        $this->body = $this->page->body;
        $this->isVisibleToClient = $this->page->is_visible_to_client;
        $this->parentId = $this->page->parent_id;
        $this->labelIds = $this->page->labels()->pluck('wiki_labels.id')->map(fn ($id): int => (int) $id)->all();
    }

    public function render(): View
    {
        return view('livewire.wiki.page-show')->title($this->page->title);
    }
}
