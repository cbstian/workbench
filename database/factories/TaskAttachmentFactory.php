<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskAttachment>
 */
class TaskAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'uploaded_by_id' => User::factory(),
            'disk' => 'local',
            'path' => 'tasks/'.fake()->uuid(),
            'original_name' => 'brief.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1200,
        ];
    }
}
