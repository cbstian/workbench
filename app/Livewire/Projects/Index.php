<?php

namespace App\Livewire\Projects;

use App\Livewire\Concerns\WithCurrentWorkspace;
use App\Livewire\Forms\ProjectForm;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

/**
 * Projects of the current workspace that the user may open (RN-21), with
 * search, client filter, sorting and pagination (TallStackUI table).
 *
 * The super admin creates projects here and sends them to the trash (F-05, RN-13).
 *
 * Logic lives here; markup lives in resources/views/livewire/projects/index.blade.php.
 */
#[Title('Proyectos')]
class Index extends Component
{
    use Interactions, WithCurrentWorkspace, WithPagination;

    public ProjectForm $form;

    public bool $showForm = false;

    /**
     * UUID of the client to filter by.
     */
    #[Url(except: '')]
    public string $client = '';

    /**
     * Columns the user may sort by, mapped to database columns.
     *
     * @var list<string>
     */
    private const SORTABLE_COLUMNS = ['name', 'is_visible_to_client', 'created_at', 'updated_at'];

    /**
     * Allowed page sizes.
     *
     * @var list<int>
     */
    public const PAGE_SIZES = [10, 25, 50];

    #[Url(as: 'q', except: '')]
    public ?string $search = '';

    public int $quantity = 10;

    /**
     * Current sort, in the shape TallStackUI's table expects.
     *
     * @var array{column: string, direction: string}
     */
    public array $sort = [
        'column' => 'updated_at',
        'direction' => 'desc',
    ];

    /**
     * Go back to the first page whenever the search term changes.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedClient(): void
    {
        $this->resetPage();
    }

    /**
     * Keep the page size within the allowed values.
     */
    public function updatedQuantity(): void
    {
        if (! in_array($this->quantity, self::PAGE_SIZES, true)) {
            $this->quantity = self::PAGE_SIZES[0];
        }

        $this->resetPage();
    }

    /**
     * Table column definitions.
     *
     * @return list<array{index: string, label: string, sortable?: bool, align?: string}>
     */
    #[Computed]
    public function headers(): array
    {
        return [
            ['index' => 'name', 'label' => 'Nombre'],
            ['index' => 'client', 'label' => 'Cliente', 'sortable' => false],
            ['index' => 'description', 'label' => 'Descripción', 'sortable' => false],
            ['index' => 'is_visible_to_client', 'label' => 'Visibilidad'],
            ['index' => 'updated_at', 'label' => 'Actualizado', 'align' => 'right'],
            ['index' => 'action', 'label' => '', 'sortable' => false, 'align' => 'right'],
        ];
    }

    /**
     * Current page of projects after search and sort.
     *
     * @return LengthAwarePaginator<int, Project>
     */
    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $column = in_array($this->sort['column'] ?? null, self::SORTABLE_COLUMNS, true)
            ? $this->sort['column']
            : 'updated_at';

        $direction = ($this->sort['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return Project::query()
            ->visibleTo(auth()->user(), $this->workspace)
            ->when($this->selectedClient, fn ($query, Client $client) => $query->where('client_id', $client->id))
            ->with('client')
            ->search($this->search)
            ->orderBy($column, $direction)
            ->orderBy('id')
            ->paginate($this->quantity)
            ->withQueryString();
    }

    #[Computed]
    public function selectedClient(): ?Client
    {
        return $this->client === '' ? null : $this->workspace->clients()->where('uuid', $this->client)->first();
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    #[Computed]
    public function clientFilterOptions(): array
    {
        return $this->workspace->clients()->orderBy('name')->get(['uuid', 'name'])
            ->map(fn (Client $client): array => ['label' => $client->name, 'value' => $client->uuid])
            ->all();
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    #[Computed]
    public function clientOptions(): array
    {
        return $this->workspace->clients()->orderBy('name')->get(['id', 'name'])
            ->map(fn (Client $client): array => ['label' => $client->name, 'value' => $client->id])
            ->all();
    }

    /**
     * Admins and members of the workspace, for the team of a new project.
     *
     * @return list<array{label: string, value: int, description: string}>
     */
    #[Computed]
    public function teamOptions(): array
    {
        return $this->workspace->members()
            ->wherePivotIn('role', ['admin', 'member'])
            ->orderBy('name')
            ->get(['users.id', 'users.name'])
            ->map(fn ($user): array => ['label' => $user->name, 'value' => $user->id, 'description' => $user->pivot->role->label()])
            ->all();
    }

    #[Computed]
    public function canCreate(): bool
    {
        return auth()->user()->can('create', [Project::class, $this->workspace]);
    }

    public function create(): void
    {
        $this->authorize('create', [Project::class, $this->workspace]);

        $this->form->reset();
        $this->form->resetValidation();
        $this->form->setWorkspace($this->workspace);
        $this->form->client_id = $this->selectedClient?->id;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('create', [Project::class, $this->workspace]);

        $project = $this->form->store(auth()->user());

        $this->showForm = false;
        $this->dispatch('projects-changed');
        $this->toast()->success('Proyecto creado', 'Nació con los estados y prioridades por defecto.')->send();

        $this->redirectRoute('projects.show', $project, navigate: true);
    }

    public function confirmDelete(string $uuid): void
    {
        $project = $this->findProject($uuid);

        $this->dialog()
            ->question("¿Enviar «{$project->name}» a la papelera?", 'Sus tareas y su wiki quedan ocultas hasta que lo restaures.')
            ->confirm('Enviar a la papelera', 'delete', $uuid)
            ->cancel('Cancelar')
            ->send();
    }

    public function delete(string $uuid): void
    {
        $project = $this->findProject($uuid);
        $this->authorize('delete', $project);

        $project->delete();

        $this->dispatch('projects-changed');
        $this->toast()->success('Proyecto enviado a la papelera')->send();
    }

    private function findProject(string $uuid): Project
    {
        return Project::query()->visibleTo(auth()->user(), $this->workspace)->where('uuid', $uuid)->firstOrFail();
    }

    public function render(): View
    {
        return view('livewire.projects.index');
    }
}
