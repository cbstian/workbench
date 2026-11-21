<?php

namespace Database\Factories;

use App\Models\WikiLabel;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WikiLabel>
 */
class WikiLabelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => 'Guía '.fake()->numerify('###'),
        ];
    }
}
