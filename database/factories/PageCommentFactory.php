<?php

namespace Database\Factories;

use App\Enums\CommentThread;
use App\Models\Page;
use App\Models\PageComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageComment>
 */
class PageCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'page_id' => Page::factory(),
            'user_id' => User::factory(),
            'body' => 'Conviene revisar este apartado antes de publicarlo.',
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
