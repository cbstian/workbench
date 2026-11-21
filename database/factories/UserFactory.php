<?php

namespace Database\Factories;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Configure the factory.
     *
     * The database fills `uuid` (uuidv7()); load it so tests can address users by it.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->setAttribute('uuid', User::withTrashed()->whereKey($user->id)->value('uuid'));
            $user->syncOriginalAttribute('uuid');
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Block sign-in without deleting the user.
     */
    public function deactivated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'deactivated_at' => now(),
        ]);
    }

    /**
     * Invite the user to a workspace with a role.
     */
    public function memberOf(Workspace $workspace, WorkspaceRole $role = WorkspaceRole::Member): static
    {
        return $this->afterCreating(function (User $user) use ($workspace, $role): void {
            $workspace->members()->attach($user, ['role' => $role]);
        });
    }
}
