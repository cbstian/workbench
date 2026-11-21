<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\TaskLabel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskLabel>
 */
class TaskLabelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => 'Etiqueta '.fake()->numerify('###'),
        ];
    }
}
