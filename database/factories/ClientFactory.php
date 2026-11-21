<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * @var list<string>
     */
    private const NAMES = ['Norte Sur', 'Taller Lumen', 'Andes Café', 'Río Azul', 'Casa Mora', 'Puerto Claro'];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->randomElement(self::NAMES),
            'description' => fake()->optional()->sentence(),
            'logo_path' => null,
            'color' => fake()->hexColor(),
        ];
    }
}
