<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectPriority;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectPriority>
 */
class ProjectPriorityFactory extends Factory
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
            'name' => 'crítica',
            'position' => 6144,
            'is_initial' => false,
        ];
    }
}
