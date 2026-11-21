<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    /**
     * @var list<string>
     */
    private const NAMES = ['Personal', 'Estudio', 'Clientes', 'Interno', 'Producto'];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'name' => fake()->randomElement(self::NAMES),
            'created_by_id' => fn (array $attributes): int => Account::query()->findOrFail($attributes['account_id'])->owner_id,
        ];
    }
}
