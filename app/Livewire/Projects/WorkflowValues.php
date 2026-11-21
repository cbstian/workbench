<?php

namespace App\Livewire\Projects;

use App\Actions\Projects\DeleteWorkflowValue;
use App\Enums\StatusCategory;
use App\Models\Project;
use App\Models\ProjectPriority;
use App\Models\ProjectStatus;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

/**
 * Statuses or priorities of one project (F-05, RN-06, RN-16, RN-22, RN-27):
 * add, rename, recolor, reorder, choose the initial value and delete.
 *
 * Used twice in the project settings page, with type "statuses" and "priorities".
 */
class WorkflowValues extends Component
{
    use Interactions;

    public const string STATUSES = 'statuses';

    public const string PRIORITIES = 'priorities';

    #[Locked]
    public Project $project;

    #[Locked]
    public string $type = self::STATUSES;

    public bool $showForm = false;

    #[Locked]
    public ?int $editingId = null;

    public string $name = '';

    public ?string $color = null;

    public string $category = 'unstarted';

    public function mount(): void
    {
        abort_unless(in_array($this->type, [self::STATUSES, self::PRIORITIES], true), 404);
        $this->authorize('configure', $this->project);
    }

    public function isStatuses(): bool
    {
        return $this->type === self::STATUSES;
    }

    /**
     * @return Collection<int, ProjectStatus|ProjectPriority>
     */
    #[Computed]
    public function values(): Collection
    {
        return $this->query()->orderBy('position')->get();
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    #[Computed]
    public function categoryOptions(): array
    {
        return array_map(fn (StatusCategory $category): array => [
            'label' => $category->label(),
            'value' => $category->value,
        ], StatusCategory::cases());
    }

    public function create(): void
    {
        $this->reset('editingId', 'name', 'color', 'category');
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $value = $this->find($id);

        $this->resetValidation();
        $this->editingId = $value->id;
        $this->name = $value->name;
        $this->color = $value->color;
        $this->category = $value instanceof ProjectStatus ? $value->category->value : 'unstarted';
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('configure', $this->project);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:64', function (string $attribute, mixed $value, Closure $fail): void {
                $taken = $this->query()
                    ->whereRaw('lower(name) = ?', [mb_strtolower(trim((string) $value))])
                    ->when($this->editingId !== null, fn (Builder $query) => $query->whereKeyNot($this->editingId))
                    ->exists();

                if ($taken) {
                    $fail('Ya existe un valor con ese nombre en este proyecto.');
                }
            }],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'category' => [Rule::requiredIf($this->isStatuses()), Rule::enum(StatusCategory::class)],
        ], ['color.regex' => 'El color debe tener el formato #RRGGBB.'], ['name' => 'nombre', 'color' => 'color', 'category' => 'categoría']);

        $attributes = [
            'name' => trim($validated['name']),
            'color' => $validated['color'] !== null ? strtolower($validated['color']) : null,
        ];

        if ($this->isStatuses()) {
            $attributes['category'] = $validated['category'];
        }

        if ($this->editingId !== null) {
            $this->find($this->editingId)->update($attributes);
        } else {
            $this->query()->create([
                ...$attributes,
                'project_id' => $this->project->id,
                'position' => ((int) $this->query()->withTrashed()->max('position')) + 1024,
                'is_initial' => false,
            ]);
        }

        $this->showForm = false;
        unset($this->values);
        $this->toast()->success($this->isStatuses() ? 'Estado guardado' : 'Prioridad guardada')->send();
    }

    /**
     * Value that new tasks take (RN-22). One initial per project.
     */
    public function makeInitial(int $id): void
    {
        $this->authorize('configure', $this->project);
        $value = $this->find($id);

        DB::transaction(function () use ($value): void {
            $this->query()->where('is_initial', true)->update(['is_initial' => false]);
            $value->update(['is_initial' => true]);
        });

        unset($this->values);
        $this->toast()->success("«{$value->name}» es ahora el valor inicial")->send();
    }

    /**
     * Swap the value with its neighbor (-1 up, 1 down).
     */
    public function move(int $id, int $direction): void
    {
        $this->authorize('configure', $this->project);

        $values = $this->values->values();
        $index = $values->search(fn ($value): bool => $value->id === $id);
        $neighbor = $index === false ? null : $values->get($index + ($direction < 0 ? -1 : 1));

        if ($neighbor === null) {
            return;
        }

        $current = $values->get($index);

        DB::transaction(function () use ($current, $neighbor): void {
            $position = $current->position;
            $current->update(['position' => $neighbor->position]);
            $neighbor->update(['position' => $position]);
        });

        unset($this->values);
    }

    public function confirmDelete(int $id): void
    {
        $value = $this->find($id);

        if ($value->is_initial) {
            $this->toast()->warning('No se puede eliminar el valor inicial', 'Elige otro como inicial primero.')->send();

            return;
        }

        $initialName = $this->values->firstWhere('is_initial', true)?->name;

        $this->dialog()
            ->question("¿Eliminar «{$value->name}»?", "Las tareas que lo usan pasan a «{$initialName}», el valor inicial del proyecto.")
            ->confirm('Eliminar', 'delete', $id)
            ->cancel('Cancelar')
            ->send();
    }

    public function delete(int $id): void
    {
        $this->authorize('configure', $this->project);

        app(DeleteWorkflowValue::class)->handle($this->find($id));

        unset($this->values);
        $this->toast()->success('Enviado a la papelera', 'Puedes restaurarlo desde la papelera.')->send();
    }

    /**
     * @return Builder<ProjectStatus>|Builder<ProjectPriority>
     */
    private function query(): Builder
    {
        $model = $this->isStatuses() ? ProjectStatus::class : ProjectPriority::class;

        return $model::query()->where('project_id', $this->project->id);
    }

    private function find(int $id): ProjectStatus|ProjectPriority
    {
        return $this->query()->findOrFail($id);
    }

    public function render(): View
    {
        return view('livewire.projects.workflow-values');
    }
}
