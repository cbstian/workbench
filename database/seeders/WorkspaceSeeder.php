<?php

namespace Database\Seeders;

use App\Enums\WorkspaceRole;
use App\Models\Client;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class WorkspaceSeeder extends Seeder
{
    /**
     * Seed clients, an admin, two members and a client user into the demo
     * workspace, and put the internal users on a few projects.
     *
     * Users: admin@workbench.test, miembro1@workbench.test, miembro2@workbench.test
     * and cliente@workbench.test, all with the password «password».
     */
    public function run(): void
    {
        $owner = User::query()->where('email', 'demo@workbench.test')->firstOrFail();
        $workspace = Workspace::query()->where('account_id', $owner->ownedAccount->id)->where('name', 'Personal')->firstOrFail();

        if ($workspace->clients()->exists()) {
            return;
        }

        $clients = collect(['Andes Café', 'Río Azul', 'Taller Lumen'])
            ->map(fn (string $name): Client => Client::factory()->for($workspace)->create(['name' => $name]));

        $projects = $workspace->projects()->inRandomOrder()->limit(12)->get();
        $projects->each(fn ($project, int $index) => $project->update(['client_id' => $clients[$index % 3]->id]));

        $people = [
            ['admin@workbench.test', 'Ana Admin', WorkspaceRole::Admin],
            ['miembro1@workbench.test', 'Mario Miembro', WorkspaceRole::Member],
            ['miembro2@workbench.test', 'Marta Miembro', WorkspaceRole::Member],
            ['cliente@workbench.test', 'Carla Cliente', WorkspaceRole::Client],
        ];

        foreach ($people as [$email, $name, $role]) {
            $user = User::factory()->create(['email' => $email, 'name' => $name]);
            $user->forceFill(['account_id' => $workspace->account_id])->save();
            $workspace->members()->attach($user->id, ['role' => $role->value, 'created_by_id' => $owner->id]);

            if ($role === WorkspaceRole::Client) {
                $user->clients()->attach($clients->first()->id);

                continue;
            }

            $projects->random(4)->each(fn ($project) => $project->members()->attach($user->id, ['created_by_id' => $owner->id]));
        }
    }
}
