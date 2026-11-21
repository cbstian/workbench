<?php

namespace App\Livewire\Concerns;

use App\Models\Project;
use Livewire\Attributes\Computed;

/**
 * Select options for the task form: statuses, priorities, team and labels
 * of the project the component works on.
 */
trait WithTaskOptions
{
    /**
     * Project whose options are listed.
     */
    abstract protected function taskOptionsProject(): Project;

    /**
     * @return list<array{label: string, value: int}>
     */
    #[Computed]
    public function statusOptions(): array
    {
        return $this->taskOptionsProject()->statuses()->get(['id', 'name'])
            ->map(fn ($status): array => ['label' => $status->name, 'value' => $status->id])
            ->all();
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    #[Computed]
    public function priorityOptions(): array
    {
        return $this->taskOptionsProject()->priorities()->get(['id', 'name'])
            ->map(fn ($priority): array => ['label' => ucfirst($priority->name), 'value' => $priority->id])
            ->all();
    }

    /**
     * Project team: the only users a task can be assigned to (RN-18).
     *
     * @return list<array{label: string, value: int}>
     */
    #[Computed]
    public function assigneeOptions(): array
    {
        return $this->taskOptionsProject()->members()->orderBy('name')->get(['users.id', 'users.name'])
            ->map(fn ($user): array => ['label' => $user->name, 'value' => $user->id])
            ->all();
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    #[Computed]
    public function labelOptions(): array
    {
        return $this->taskOptionsProject()->taskLabels()->orderBy('name')->get(['id', 'name'])
            ->map(fn ($label): array => ['label' => $label->name, 'value' => $label->id])
            ->all();
    }
}
