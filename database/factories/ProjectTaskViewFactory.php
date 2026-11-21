<?php

namespace Database\Factories;

use App\Enums\TaskView;
use App\Models\Project;
use App\Models\ProjectTaskView;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectTaskView>
 */
class ProjectTaskViewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
            'view' => TaskView::Kanban,
            'filters' => [
                'status_ids' => [],
                'priority_ids' => [],
                'assignee_ids' => [],
                'label_ids' => [],
                'starts_from' => null,
                'starts_to' => null,
                'ends_from' => null,
                'ends_to' => null,
            ],
        ];
    }
}
