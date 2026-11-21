<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Seed demo projects into the demo user's workspace.
     */
    public function run(): void
    {
        $owner = User::query()->where('email', 'demo@workbench.test')->firstOrFail();
        $workspace = $owner->ownedWorkspaces()->firstOrFail();

        Project::factory()->count(36)->for($workspace)->create();
    }
}
