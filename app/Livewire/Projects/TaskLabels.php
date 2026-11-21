<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\TaskLabel;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

/**
 * Task labels of one project (F-05, RN-06): no defaults; add, edit and delete.
 */
class TaskLabels extends Component
{
    use Interactions;

    #[Locked]
    public Project $project;

    public bool $showForm = false;

    #[Locked]
    public ?int $editingId = null;

    public string $name = '';

    public ?string $color = null;

    public function mount(): void
    {
        $this->authorize('configure', $this->project);
    }

    /**
     * @return Collection<int, TaskLabel>
     */
    #[Computed]
    public function labels(): Collection
    {
        return $this->project->taskLabels()->orderBy('name')->get();
    }

    public function create(): void
    {
        $this->reset('editingId', 'name', 'color');
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $label = $this->project->taskLabels()->findOrFail($id);

        $this->resetValidation();
        $this->editingId = $label->id;
        $this->name = $label->name;
        $this->color = $label->color;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('configure', $this->project);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:64', function (string $attribute, mixed $value, Closure $fail): void {
                $taken = $this->project->taskLabels()
                    ->whereRaw('lower(name) = ?', [mb_strtolower(trim((string) $value))])
                    ->when($this->editingId !== null, fn ($query) => $query->whereKeyNot($this->editingId))
                    ->exists();

                if ($taken) {
                    $fail('Ya existe una etiqueta con ese nombre en este proyecto.');
                }
            }],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], ['color.regex' => 'El color debe tener el formato #RRGGBB.'], ['name' => 'nombre', 'color' => 'color']);

        $attributes = [
            'name' => trim($validated['name']),
            'color' => $validated['color'] !== null ? strtolower($validated['color']) : null,
        ];

        $this->editingId !== null
            ? $this->project->taskLabels()->findOrFail($this->editingId)->update($attributes)
            : $this->project->taskLabels()->create($attributes);

        $this->showForm = false;
        unset($this->labels);
        $this->toast()->success('Etiqueta guardada')->send();
    }

    public function delete(int $id): void
    {
        $this->authorize('configure', $this->project);

        $this->project->taskLabels()->findOrFail($id)->delete();

        unset($this->labels);
        $this->toast()->success('Etiqueta enviada a la papelera', 'Las tareas dejan de mostrarla hasta que la restaures.')->send();
    }

    public function render(): View
    {
        return view('livewire.projects.task-labels');
    }
}
