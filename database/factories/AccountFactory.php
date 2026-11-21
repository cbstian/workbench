<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'name' => fake()->company(),
        ];
    }

    /**
     * Configure the factory.
     *
     * The owner is managed by their own account, as when it is opened from the console.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Account $account): void {
            $account->owner()->update(['account_id' => $account->id]);
        });
    }
}
