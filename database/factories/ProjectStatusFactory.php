<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectStatus>
 */
class ProjectStatusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * A project factory already inserts the default initial status, so an extra
     * status stays non-initial and avoids the partial unique index.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => 'Revisión',
            'position' => 10,
            'is_initial' => false,
        ];
    }
}
