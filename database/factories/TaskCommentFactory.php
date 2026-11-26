<?php

namespace Database\Factories;

use App\Enums\CommentThread;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskComment>
 */
class TaskCommentFactory extends Factory
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
            'user_id' => User::factory(),
            'body' => 'Quedó anotado para la próxima revisión.',
        ];
    }

    /**
     * A message of the conversation with the client (P-04).
     */
    public function inClientThread(): static
    {
        return $this->state(fn (): array => ['thread' => CommentThread::Client]);
    }
}
