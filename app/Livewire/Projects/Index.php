<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Projects list with search, sorting and pagination (TallStackUI table).
 *
 * Logic lives here; markup lives in resources/views/livewire/projects/index.blade.php.
 */
#[Title('Proyectos')]
class Index extends Component
{
    use WithPagination;

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
            ['index' => 'description', 'label' => 'Descripción', 'sortable' => false],
            ['index' => 'is_visible_to_client', 'label' => 'Cliente'],
            ['index' => 'updated_at', 'label' => 'Actualizado', 'align' => 'right'],
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
            ->search($this->search)
            ->orderBy($column, $direction)
            ->orderBy('id')
            ->paginate($this->quantity)
            ->withQueryString();
    }

    public function render(): View
    {
        return view('livewire.projects.index');
    }
}
