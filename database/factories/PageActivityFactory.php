<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\PageActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageActivity>
 */
class PageActivityFactory extends Factory
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
            'event' => 'updated',
            'properties' => ['after' => ['title' => 'Inicio']],
        ];
    }
}
