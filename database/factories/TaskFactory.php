<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectPriority;
use App\Models\ProjectStatus;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * @var list<string>
     */
    private const NAMES = [
        'Preparar propuesta',
        'Revisar contrato',
        'Publicar landing',
        'Cargar inventario',
        'Cerrar sprint',
        'Escribir guía',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'parent_id' => null,
            'status_id' => function (array $attributes): int {
                return (int) ProjectStatus::query()
                    ->where('project_id', $attributes['project_id'])
                    ->where('is_initial', true)
                    ->value('id');
            },
            'priority_id' => function (array $attributes): int {
                return (int) ProjectPriority::query()
                    ->where('project_id', $attributes['project_id'])
                    ->where('is_initial', true)
                    ->value('id');
            },
            'name' => fake()->randomElement(self::NAMES),
            'description' => fake()->optional()->sentence(),
            'starts_on' => null,
            'ends_on' => null,
            'position' => 0,
        ];
    }

    /**
     * Make a subtask of a root task, in the same project.
     */
    public function subtaskOf(Task $task): static
    {
        return $this->state(fn (): array => [
            'project_id' => $task->project_id,
            'parent_id' => $task->id,
            'status_id' => $task->status_id,
            'priority_id' => $task->priority_id,
        ]);
    }
}
